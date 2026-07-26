<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\StoreMessageJob;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Chat;
use App\Application\Context\ChatContext;
use App\Application\Pipelines\ChatPipeline;
use App\Domain\Contracts\MessageStore;

class ChatController extends Controller
{
    public function __construct(
        protected ChatPipeline $chatPipeline,
        protected MessageStore $messageStore
    ) {
    }

    /**
     * لیست چت‌ها
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();
        $cacheKey = "user:{$userId}:chats:last20";

        $chats = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($userId) {
                return Chat::where('user_id', $userId)
                    ->with([
                        'messages' => function ($q) {
                            $q->latest()->limit(1);
                        }
                    ])
                    ->orderBy('updated_at', 'desc')
                    ->limit(20)
                    ->get();
            }
        );

        return response()->json($chats);
    }

    /**
     * پیام‌های یک چت
     */
    public function messages(Chat $chat, Request $request): JsonResponse
    {
        if ($chat->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $perPage = (int) $request->input('per_page', 20);
        $page = (int) $request->input('page', 1);
        $cacheKey = "chat:{$chat->id}:messages:page:{$page}:limit:{$perPage}";

        $messages = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($chat, $perPage, $page) {
                $paginator = $chat->messages()
                    ->orderBy('created_at', 'asc')
                    ->paginate($perPage, ['*'], 'page', $page);

                return [
                    'data' => $paginator->items(),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ]
                ];
            }
        );

        return response()->json($messages);
    }

    /**
     * ارسال پیام و پاسخ Stream آنی بدون تاخیر
     */
    /**
     * ارسال پیام و پاسخ Stream آنی بدون تاخیر
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'message' => 'nullable|string',
            'chat_id' => 'nullable|exists:chats,id',
            'model_id' => 'required|exists:ai_models,id',
            'image' => 'nullable|image|max:5120',
            'paper_id' => 'nullable|exists:papers,id',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'exists:documents,id'
        ]);

        /*
        |--------------------------------------------------------------------------
        | ۱. دریافت Workspace فعال
        |--------------------------------------------------------------------------
        */
        $workspace = $user->workspaces()
            ->where('is_active', true)
            ->first();

        if (!$workspace) {
            return response()->json(['error' => 'No active workspace'], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | ۲. بررسی اشتراک فعال
        |--------------------------------------------------------------------------
        */
        $subscription = $workspace->activeSubscription();

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription'], 403);
        }

        $subscription->load('workspacePlan.models');

        if (!$subscription->workspacePlan) {
            return response()->json(['error' => 'No workspace plan associated with subscription'], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | ۳. بررسی دسترسی به مدل AI
        |--------------------------------------------------------------------------
        */
        $aiModel = $subscription->workspacePlan
            ->models()
            ->where('ai_models.id', $request->model_id)
            ->first();

        if (!$aiModel) {
            return response()->json(['error' => 'Model unavailable'.$subscription->workspacePlan], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | ۴. آپلود تصویر در صورت وجود
        |--------------------------------------------------------------------------
        */
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat-images', 'public');
        }
        $imageUrl = $imagePath ? asset('storage/' . $imagePath) : null;

        /*
        |--------------------------------------------------------------------------
        | ۵. ساخت یا یافتن چت (با اعتبارسنجی تعلق به workspace)
        |--------------------------------------------------------------------------
        */
        if ($request->chat_id) {
            $chat = Chat::where('id', $request->chat_id)
                ->where('workspace_id', $workspace->id)
                ->first();

            if (!$chat) {
                return response()->json(['error' => 'Chat not found in this workspace'], 404);
            }
        } else {
            $chat = Chat::create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
                'title' => mb_substr($request->message ?? 'New Chat', 0, 50)
            ]);
        }

        Cache::forget("user:{$user->id}:chats:last20");

        $content = $request->message ?? '';

        /*
        |--------------------------------------------------------------------------
        | ۶. ارسال Async پیام کاربر به صف دیتابیس
        |--------------------------------------------------------------------------
        */
        StoreMessageJob::dispatch([
            'id' => uniqid(),
            'conversation' => (string) $chat->id,
            'user_id' => (string) $user->id,
            'workspace_id' => $workspace->id,
            'role' => 'user',
            'content' => $content,
            'token_count' => 0,
            'metadata' => [
                'model_id' => $aiModel->id,
                'document_ids' => $request->document_ids ?? [],
                'attachments' => $imagePath ? [['type' => 'image', 'url' => $imageUrl]] : []
            ],
            'created_at' => now()->toDateTimeString()
        ]);

        /*
        |--------------------------------------------------------------------------
        | ۷. ساخت Context
        |--------------------------------------------------------------------------
        */
        $context = new ChatContext(
            userId: (string) $user->id,
            conversationId: (string) $chat->id,
            currentMessage: $content,
            model: $aiModel->key
        );

        $context->setMetadata([
            'workspace_id' => $workspace->id,
            'model_id' => $aiModel->id,
            'document_ids' => $request->document_ids ?? [],
            'attachments' => $imagePath ? [['type' => 'image', 'url' => $imageUrl]] : []
        ]);

        /*
        |--------------------------------------------------------------------------
        | بستن سشن برای جلوگیری از قفل شدن درخواست‌های همزمان کاربر
        |--------------------------------------------------------------------------
        */
        if (request()->hasSession()) {
            session()->writeClose();
        }

        /*
        |--------------------------------------------------------------------------
        | ۸. ارسال Response Stream بهینه شده و مقاوم
        |--------------------------------------------------------------------------
        */
        return response()->stream(
            function () use ($context, $workspace) {
                // تنظیمات برای خاموش کردن کامل بافرینگ سرور
                @ini_set('zlib.output_compression', 'Off');
                @ini_set('output_buffering', 'Off');
                @ini_set('implicit_flush', '1');

                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }

                set_time_limit(0); // جلوگیری از Timeout شدن اسکریپت در استریم‌های طولانی

                try {
                    $result = $this->chatPipeline->stream($context);
                    $fullResponse = '';

                    foreach ($result as $chunk) {
                        // بررسی قطع اتصال کاربر (Abort شدن کلاینت)
                        if (connection_aborted()) {
                            break;
                        }

                        $text = '';

                        if (isset($chunk['content'])) {
                            $text = $chunk['content'];
                        } elseif (isset($chunk['choices'][0]['delta']['content'])) {
                            $text = $chunk['choices'][0]['delta']['content'];
                        }

                        if ($text === '') {
                            continue;
                        }

                        $fullResponse .= $text;

                        // ارسال استاندارد SSE
                        echo "data: " . json_encode(['content' => $text], JSON_UNESCAPED_UNICODE) . "\n\n";

                        if (ob_get_level() > 0) {
                            @ob_flush();
                        }
                        @flush();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ذخیره پیام نهایی هوش مصنوعی پس از اتمام استریم
                    |--------------------------------------------------------------------------
                    */
                    if ($fullResponse !== '') {
                        StoreMessageJob::dispatch([
                            'id' => uniqid(),
                            'conversation' => (string) $context->conversationId,
                            'user_id' => (string) $context->userId,
                            'workspace_id' => $workspace->id,
                            'role' => 'assistant',
                            'content' => $fullResponse,
                            'token_count' => 0,
                            'metadata' => [
                                'model' => $context->model,
                                'document_ids' => $context->metadata['document_ids'] ?? []
                            ],
                            'created_at' => now()->toDateTimeString()
                        ]);
                    }

                    echo "data: [DONE]\n\n";
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    @flush();

                } catch (\Exception $e) {
                    \Log::error("Chat Stream Error: " . $e->getMessage());
                    echo "data: " . json_encode(['error' => 'Stream failed'], JSON_UNESCAPED_UNICODE) . "\n\n";
                    @flush();
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no', // حیاتی برای Nginx جهت جلوگیری از بافرینگ
                'Content-Encoding' => 'none',
            ]
        );
    }

    /**
     * نمایش چت
     */
    public function show(Workspace $workspace, Chat $chat)
    {
        if ($chat->workspace_id !== $workspace->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chat does not belong to workspace'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'chat' => $chat->load('messages')
        ]);
    }

    /**
     * حذف چت
     */
    public function destroy(Chat $chat): JsonResponse
    {
        if ($chat->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $userId = auth()->id();
        $chatId = $chat->id;

        $chat->messages()->delete();
        $chat->delete();

        Cache::forget("user:{$userId}:chats:last20");
        Cache::forget("chat:{$chatId}:messages:page:1:limit:50");

        return response()->json([
            'success' => true,
            'message' => 'deleted'
        ]);
    }
}
