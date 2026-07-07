<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use OpenAI\Exceptions\RateLimitException;

class ChatController extends Controller
{
    // تعداد پیام‌های ارسالی به OpenAI (آخرین N پیام)
    protected $contextLimit = 50;

    /**
     * دریافت لیست چت‌های کاربر (فقط متعلق به خودش)
     */
    public function index(): JsonResponse
    {
        $chats = Chat::where('user_id', auth()->id())
            ->with('messages')
            ->orderBy('updated_at', 'desc')
            ->get();
        return response()->json($chats);
    }

    /**
     * ایجاد چت جدید یا دریافت پاسخ از ChatGPT
     * با قابلیت: محدودیت روزانه، حافظه‌ی کاربر، خلاصه‌سازی هوشمند، ارسال تصویر
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        // بررسی احراز هویت
        if (!$user) {
            return response()->json(['error' => 'احراز هویت نشده‌اید.'], 401);
        }

        // بررسی محدودیت روزانه
        if (!$user->canSendMessage()) {
            return response()->json([
                'error' => 'شما به محدودیت روزانه‌ی خود رسیده‌اید. (حداکثر ' . $user->dailyMessageLimit() . ' پیام در روز)'
            ], 429);
        }

        $request->validate([
            'message' => 'nullable|string',
            'chat_id' => 'nullable|exists:chats,id',
            'image'   => 'nullable|image|max:5120',
        ]);

        // ۱. ایجاد یا یافتن چت (با اطمینان از تعلق به کاربر)
        $chat = $request->chat_id
            ? Chat::where('user_id', $user->id)->findOrFail($request->chat_id)
            : Chat::create([
                'user_id' => $user->id,
                'title'   => $request->filled('message') ? mb_substr($request->message, 0, 50) : 'چت جدید',
            ]);

        // ۲. پردازش تصویر (در صورت وجود)
        $imageBase64 = null;
        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $imageData = file_get_contents($imageFile->getRealPath());
            $mimeType = $imageFile->getMimeType();
            $imageBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }

        // ۳. ساخت محتوای پیام کاربر
        $userContent = '';
        if ($request->filled('message')) {
            $userContent = $request->message;
        }
        if ($imageBase64) {
            $userContent = $userContent ? $userContent . "\n" . $imageBase64 : $imageBase64;
        }

        if (empty($userContent)) {
            return response()->json(['error' => 'پیام یا تصویر الزامی است.'], 422);
        }

        // ۴. ذخیره پیام کاربر (با user_id)
        $userMessage = $chat->messages()->create([
            'user_id' => $user->id,
            'role'    => 'user',
            'content' => $userContent,
        ]);

        // ۵. استخراج اطلاعات کاربر از متن پیام (حافظه)
        $this->extractMemoriesFromText($user, $request->message);

        // ۶. آماده‌سازی تاریخچه‌ی چت (با خلاصه‌سازی و محدودیت تعداد)
        $history = $this->prepareChatHistory($chat, $user);

        // ۷. ساخت payload نهایی برای OpenAI
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($user)]],
            $history,
            $this->buildUserPayload($request, $imageBase64)
        );

        // ۸. فراخوانی OpenAI
        try {
            $model = $user->currentPlan()->model ?? 'gpt-4o-mini';
            $response = OpenAI::chat()->create([
                'model'      => $model,
                'messages'   => $messages,
                'max_tokens' => 800,
            ]);

            $assistantContent = $response->choices[0]->message->content ?? 'پاسخی دریافت نشد.';

            // ذخیره پاسخ دستیار
            $assistantMessage = $chat->messages()->create([
                'user_id' => $user->id,
                'role'    => 'assistant',
                'content' => $assistantContent,
            ]);

            // ۹. خلاصه‌سازی خودکار در صورت نیاز
            $this->maybeSummarizeChat($chat);

            return response()->json([
                'chat'    => $chat->load('messages'),
                'message' => $assistantMessage,
            ]);

        } catch (RateLimitException $e) {
            Log::warning('Rate limit exceeded: ' . $e->getMessage());
            return response()->json([
                'error' => 'محدودیت درخواست به OpenAI. لطفاً چند دقیقه صبر کنید و دوباره تلاش کنید.'
            ], 429);
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'خطا در ارتباط با OpenAI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * دریافت یک چت با تمام پیام‌ها (فقط متعلق به کاربر)
     */
    public function show(Chat $chat): JsonResponse
    {
        if ($chat->user_id !== auth()->id()) {
            return response()->json(['error' => 'دسترسی غیرمجاز'], 403);
        }
        return response()->json($chat->load('messages'));
    }

    /**
     * حذف یک چت (فقط متعلق به کاربر)
     */
    public function destroy(Chat $chat): JsonResponse
    {
        if ($chat->user_id !== auth()->id()) {
            return response()->json(['error' => 'دسترسی غیرمجاز'], 403);
        }
        $chat->delete();
        return response()->json(['message' => 'چت با موفقیت حذف شد.']);
    }

    // ------------------------------------------------------------
    // متدهای کمکی (خصوصی)
    // ------------------------------------------------------------

    /**
     * ساخت سیستم پرامپت (شامل حافظه‌های کاربر)
     */
    private function buildSystemPrompt($user): string
    {
        $memories = Memory::where('user_id', $user->id)
            ->where('last_used_at', '>', now()->subDays(30))
            ->get();

        $memoryText = $memories->map(fn($m) => "{$m->key}: {$m->value}")->implode(' - ');
        $basePrompt = 'شما یک دستیار هوشمند و مفید هستید. به فارسی پاسخ دهید.';

        if ($memoryText) {
            $basePrompt .= "\nاطلاعات کاربر: {$memoryText}";
        }

        return $basePrompt;
    }

    /**
     * آماده‌سازی تاریخچه‌ی چت (با خلاصه‌سازی و محدودیت تعداد)
     */
    private function prepareChatHistory(Chat $chat, $user): array
    {
        // ۱. اگر خلاصه‌ای وجود دارد و تعداد پیام‌ها زیاد است، از خلاصه استفاده کن
        if ($chat->summary && $chat->messages()->count() > 100) {
            return [
                ['role' => 'system', 'content' => "خلاصه‌ی مکالمه‌های قبلی: {$chat->summary}"]
            ];
        }

        // ۲. در غیر این صورت، آخرین N پیام را بگیر (همان $contextLimit)
        $messages = $chat->messages()
            ->orderBy('created_at', 'desc')
            ->limit($this->contextLimit)
            ->get()
            ->reverse();

        return $messages->map(fn($msg) => [
            'role'    => $msg->role,
            'content' => $msg->content,
        ])->toArray();
    }

    /**
     * ساخت payload برای ارسال به OpenAI (چندرسانه‌ای)
     */
    private function buildUserPayload(Request $request, ?string $imageBase64): array
    {
        $payload = [];
        if ($request->filled('message')) {
            $payload[] = ['type' => 'text', 'text' => $request->message];
        }
        if ($imageBase64) {
            $payload[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $imageBase64],
            ];
        }
        return $payload ? [['role' => 'user', 'content' => $payload]] : [];
    }

    /**
     * استخراج حافظه از متن کاربر (با الگوهای فارسی)
     */
    private function extractMemoriesFromText($user, ?string $text)
    {
        if (empty($text)) return;

        $patterns = [
            '/اسم من (.*?) (است|هست)/'                              => 'name',
            '/من (.*?) هستم/'                                       => 'job',
            '/شغل من (.*?) (است|هست)/'                             => 'job',
            '/علاقه مند به (.*?) (هستم|دارم)/'                     => 'interest',
            '/از (.*?) خوشم میاد/'                                 => 'like',
            '/اهل (.*?) (هستم|می‌باشم)/'                           => 'hometown',
            '/تاریخ تولد من (.*?) (است|هست)/'                     => 'birthday',
            '/آدرس من (.*?) (است|هست)/'                            => 'address',
            '/دوران تحصیل (.*?) (خواندم|می‌خوانم)/'                => 'education',
        ];

        foreach ($patterns as $pattern => $key) {
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1]);
                if (!empty($value)) {
                    Memory::updateOrCreate(
                        ['user_id' => $user->id, 'key' => $key],
                        [
                            'value'        => $value,
                            'source'       => 'explicit',
                            'last_used_at' => now(),
                        ]
                    );
                }
                break; // فقط اولین الگوی پیدا شده را ذخیره کن
            }
        }

        // (اختیاری) استخراج با OpenAI برای دقت بیشتر – در صورت نیاز فعال کنید
        // $this->extractMemoriesWithAI($user, $text);
    }

    /**
     * (اختیاری) استخراج حافظه با استفاده از OpenAI (پیشنهاد برای دقت بیشتر)
     * این متد هزینه‌ی اضافی دارد، بنابراین با احتیاط استفاده کنید.
     */
    private function extractMemoriesWithAI($user, $text)
    {
        // فقط هر ۱۰ پیام یک‌بار یا در صورت وجود اطلاعات جدید
        $prompt = "از متن زیر، اطلاعات شخصی کاربر (نام، شغل، علاقه‌مندی، شهر، ...) را به‌صورت JSON با کلیدهای 'key' و 'value' استخراج کن.\nمتن: $text";
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.2,
                'max_tokens' => 200,
            ]);
            $data = json_decode($response->choices[0]->message->content, true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    Memory::updateOrCreate(
                        ['user_id' => $user->id, 'key' => $item['key']],
                        [
                            'value' => $item['value'],
                            'source' => 'inferred',
                            'last_used_at' => now(),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning('Memory extraction with AI failed: ' . $e->getMessage());
        }
    }

    /**
     * خلاصه‌سازی خودکار چت (در صورت زیاد شدن پیام‌ها)
     */
    private function maybeSummarizeChat(Chat $chat)
    {
        $messageCount = $chat->messages()->count();
        // اگر تعداد پیام‌ها از ۱۰۰ بیشتر شد و خلاصه‌ای وجود نداشت، خلاصه بساز
        if ($messageCount > 100 && is_null($chat->summary)) {
            // گرفتن ۸۰ پیام قدیمی (به‌جز ۲۰ پیام آخر که تازه‌ترین‌ها هستند)
            $oldMessages = $chat->messages()
                ->orderBy('created_at', 'desc')
                ->skip(20)
                ->take(80)
                ->get()
                ->reverse();

            if ($oldMessages->isEmpty()) return;

            $textToSummarize = $oldMessages->map(fn($m) => "{$m->role}: {$m->content}")->implode("\n");

            try {
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'خلاصه‌ای کوتاه از مکالمه‌ی زیر به فارسی بنویس (حداکثر ۲۰۰ کلمه).'],
                        ['role' => 'user', 'content' => $textToSummarize],
                    ],
                    'max_tokens' => 300,
                ]);

                $summary = $response->choices[0]->message->content ?? '';
                if ($summary) {
                    $chat->update(['summary' => $summary]);
                }
            } catch (\Exception $e) {
                Log::error('Summarization failed: ' . $e->getMessage());
            }
        }
    }
}
