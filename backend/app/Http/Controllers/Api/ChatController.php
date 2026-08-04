<?php

namespace App\Http\Controllers\Api;

use App\Application\Pipelines\WorkspacePipelineFactory;
use App\Http\Controllers\Controller;
use App\Application\Context\ChatContext;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ChatController extends Controller
{
    public function __construct(
        protected WorkspacePipelineFactory $pipelineFactory
    ) {
    }

    public function checkAccess(Request $request, $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'has_access' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $feature = $request->query('feature');

        $workspace = $user->workspaces()
            ->with(['workspaceType', 'plan.models'])
            ->where('workspaces.id', $id)
            ->where('workspaces.is_active', true)
            ->first();

        if (!$workspace) {
            return response()->json([
                'has_access' => false,
                'message' => 'Workspace not found or unauthorized'
            ], 403);
        }

        if ($feature) {
            $workspaceType = $workspace->workspaceType;
            $features = $workspaceType?->features;

            $hasFeature = is_array($features) && collect($features)
                ->contains(fn($item) => ($item['key'] ?? null) === $feature);

            if (!$hasFeature) {
                return response()->json([
                    'has_access' => false,
                    'message' => 'Feature not available in this workspace type'
                ], 403);
            }
        }

        return response()->json([
            'has_access' => true,
            'workspace' => $workspace
        ]);
    }

    public function index($workspace_id): JsonResponse
    {
        $userId = auth()->id();
        $cacheKey = "user:{$userId}:chats:last20";

        $chats = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($userId, $workspace_id) {
                return Chat::where(['user_id' => $userId, "workspace_id" => $workspace_id])
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

    public function messages(Chat $chat, Request $request): JsonResponse
    {
        if ($chat->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Forbidden'
            ], 403);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $page = (int) $request->input('page', 1);
        $cacheKey = "chat:{$chat->id}:messages:{$page}:{$perPage}";

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
                        'total' => $paginator->total()
                    ]
                ];
            }
        );

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $this->validateIncomingRequest($request);

        $workspace = $this->resolveActiveWorkspace($user, $request->workspace_id);
        if (!$workspace) {
            return response()->json([
                'success' => false,
                'error' => 'No active workspace',
                'message' => 'workspace فعالی برای شما یافت نشد.'
            ], 403);
        }

        $content = trim($request->message ?? '');
        if ($response = $this->handleEmptyMessageValidation($request, $content)) {
            return $response;
        }

        $aiModel = $this->resolveAuthorizedModel($workspace, $request->model_id);
        if (!$aiModel) {
            return response()->json([
                'success' => false,
                'error' => 'Model unavailable',
                'message' => "مدل انتخاب شده در اشتراک فعلی شما در دسترس نیست."
            ], 403);
        }

        $chat = $this->resolveOrCreateChat($request, $workspace, $user, $content);
        if (!$chat) {
            return response()->json(['success' => false, 'error' => 'Chat not found or unauthorized'], 404);
        }

        Cache::forget("user:{$user->id}:chats:last20");

        $imageBase64 = $this->processImageAttachment($request);

        $pdfResult = $this->processPdfAttachment($request);
        if (isset($pdfResult['error_response'])) {
            return $pdfResult['error_response'];
        }
        $extractedFileText = $pdfResult['text'] ?? null;

        $finalMessageText = $this->buildFinalMessageText($content, $extractedFileText);

        $context = $this->createChatContext($user, $chat, $aiModel, $workspace, $finalMessageText, $request, $imageBase64, $extractedFileText);

        return $this->streamPipelineResponse($workspace, $context);
    }

    protected function validateIncomingRequest(Request $request): void
    {
        $request->validate([
            'workspace_id' => 'required|string',
            'message' => 'nullable|string|max:50000',
            'chat_id' => 'nullable|exists:chats,id',
            'model_id' => 'required|exists:ai_models,id',
            'file' => 'nullable|file|max:1024',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|min:4096|max:30720',
            'pdf' => 'nullable|file|mimes:pdf|max:40960',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'exists:documents,id',
            'paper_id' => 'nullable|exists:papers,id'
        ], [
            'image.min' => 'حجم تصویر انتخابی باید حداقل ۴ مگابایت باشد.',
            'image.max' => 'حجم تصویر نمی‌تواند بیشتر از ۳۰ مگابایت باشد.',
            'file.max' => 'حجم فایل‌های متفرقه نباید بیشتر از ۱ مگابایت باشد.',
            'pdf.max' => 'حجم فایل PDF نباید بیشتر از ۴۰ مگابایت باشد.'
        ]);
    }

    protected function resolveActiveWorkspace($user, string $workspaceId)
    {
        return $user->workspaces()
            ->where('is_active', true)
            ->where('workspaces.id', $workspaceId)
            ->first();
    }

    protected function handleEmptyMessageValidation(Request $request, string $content)
    {
        if (empty($content)) {
            if (!empty($request->document_ids) || $request->hasFile('pdf') || $request->hasFile('image') || $request->hasFile('file')) {
                return response()->stream(function () {
                    $noticeText = "فایل انتخاب شد. لطفاً یک پیام یا سوال بنویسید.";
                    foreach (mb_str_split($noticeText) as $char) {
                        echo "data: " . json_encode(['content' => $char], JSON_UNESCAPED_UNICODE) . "\n\n";
                        if (ob_get_level() > 0)
                            ob_flush();
                        flush();
                        usleep(25000);
                    }
                    echo "data: [DONE]\n\n";
                    flush();
                }, 200, [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'X-Accel-Buffering' => 'no'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Empty message',
                'message' => 'متن پیام نمی‌تواند خالی باشد.'
            ], 422);
        }

        return null;
    }

    protected function resolveAuthorizedModel($workspace, int $modelId)
    {
        // اطمینان از اینکه کوئری مستقیماً روی بیلدر زده می‌شود و از get() استفاده نشده است
        $subscription = $workspace->activeSubscription()
            ->with('workspacePlan.models')
            ->first();

        if (!$subscription) {
            return null;
        }

        // بررسی اینکه آیا روابط به درستی بارگذاری شده‌اند یا خیر
        if (!$subscription->relationLoaded('workspacePlan')) {
            $subscription->load('workspacePlan.models');
        }

        return $subscription->workspacePlan?->models?->firstWhere('id', $modelId);
    }

    protected function resolveOrCreateChat(Request $request, $workspace, $user, string $content)
    {
        if ($request->filled('chat_id')) {
            return Chat::where('id', $request->chat_id)
                ->where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->first();
        }

        return Chat::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'model_id' => $request->model_id,
            'title' => mb_substr($content, 0, 50)
        ]);
    }

    protected function processImageAttachment(Request $request): ?string
    {
        if (!$request->hasFile('image'))
            return null;

        $uploadedFile = $request->file('image');
        $path = $uploadedFile->getRealPath();
        $info = @getimagesize($path);
        $mime = $info['mime'] ?? 'image/jpeg';

        $imageResource = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };

        if (!$imageResource)
            return null;

        $width = imagesx($imageResource);
        $height = imagesy($imageResource);
        $maxWidth = 800;

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = floor($height * ($maxWidth / $width));
            $tmpImg = imagecreatetruecolor($newWidth, $newHeight);

            if ($mime == 'image/png' || $mime == 'image/webp') {
                imagecolortransparent($tmpImg, imagecolorallocatealpha($tmpImg, 0, 0, 0, 127));
                imagealphablending($tmpImg, false);
                imagesavealpha($tmpImg, true);
            }

            imagecopyresampled($tmpImg, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($imageResource);
            $imageResource = $tmpImg;
        }

        ob_start();
        imagejpeg($imageResource, null, 75);
        $compressedData = ob_get_clean();
        imagedestroy($imageResource);

        return 'data:image/jpeg;base64,' . base64_encode($compressedData);
    }

    protected function processPdfAttachment(Request $request): array
    {
        $targetFile = $request->file('pdf') ?? $request->file('file');
        if (!$targetFile)
            return ['text' => null];

        $tempPath = $targetFile->store('temp_pdfs', 'local');
        $fullFilePath = Storage::disk('local')->path($tempPath);

        try {
            if (!file_exists($fullFilePath))
                return ['text' => null];

            $pythonPath = file_exists(base_path('venv/bin/python3')) ? base_path('venv/bin/python3') : 'python3';
            $scriptPath = base_path('storage/scripts/extract_text.py');

            $process = new Process([$pythonPath, $scriptPath, $fullFilePath]);
            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                \Log::error("Python PDF Extraction Failed: " . $process->getErrorOutput());
                return ['text' => '[خطا در استخراج متن از فایل پیوست]'];
            }

            $outputData = json_decode(trim($process->getOutput()), true);

            if (is_array($outputData) && isset($outputData['pages'])) {
                if ($outputData['pages'] > 50) {
                    return [
                        'error_response' => response()->json([
                            'success' => false,
                            'error' => 'Too many pages',
                            'message' => 'تعداد صفحات فایل PDF نمی‌تواند بیشتر از ۵۰ صفحه باشد.'
                        ], 422)
                    ];
                }
                return ['text' => $outputData['text'] ?? ''];
            }

            return ['text' => trim($process->getOutput())];
        } finally {
            if (Storage::disk('local')->exists($tempPath)) {
                Storage::disk('local')->delete($tempPath);
            }
        }
    }

    protected function buildFinalMessageText(string $content, ?string $extractedText): string
    {
        $finalText = $content;
        if (!empty($extractedText)) {
            $finalText .= "\n\n[محتوای سند پیوست‌شده]:\n" . $extractedText;
        }
        return $finalText;
    }

    protected function createChatContext($user, $chat, $aiModel, $workspace, string $finalMessageText, Request $request, ?string $imageBase64, ?string $extractedFileText): ChatContext
    {
        $context = new ChatContext(
            userId: (string) $user->id,
            conversationId: (string) $chat->id,
            currentMessage: json_encode([
                'text' => $finalMessageText,
                'image' => $imageBase64,
                'file' => null
            ], JSON_UNESCAPED_UNICODE),
            model: $aiModel->key
        );

        $context->setWorkspace($workspace);
        $context->setMetadata([
            'workspace_id' => $workspace->id,
            'model_id' => $aiModel->id,
            'document_ids' => array_map('strval', $request->document_ids ?? []),
            'paper_id' => $request->paper_id,
            'attachments' => [
                'image_base64' => $imageBase64,
                'extracted_text' => $extractedFileText
            ]
        ]);

        return $context;
    }

    protected function streamPipelineResponse($workspace, ChatContext $context)
    {
        $pipeline = $this->pipelineFactory->make($workspace["type"]);

        return response()->stream(
            function () use ($pipeline, $context) {
                try {
                    $pipeline->stream(
                        $context,
                        function ($chunk) {
                            echo "data: " . json_encode($chunk, JSON_UNESCAPED_UNICODE) . "\n\n";
                            if (ob_get_level() > 0)
                                ob_flush();
                            flush();
                        }
                    );
                } catch (\Throwable $e) {
                    \Log::error("Pipeline Stream Error: " . $e->getMessage());
                    echo "data: " . json_encode(['error' => 'خطا در پردازش درخواست.'], JSON_UNESCAPED_UNICODE) . "\n\n";
                    flush();
                }

                echo "data: [DONE]\n\n";
                flush();
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no'
            ]
        );
    }

    public function show($workspace_id, $chatId): JsonResponse
    {
        $chat = Chat::with('messages')
            ->where('id', $chatId)
            ->where('workspace_id', $workspace_id)
            ->first();

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'چت وجود ندارد',
                'code' => 404
            ], 404);
        }

        if ($chat->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
                'code' => 403
            ], 403);
        }

        return response()->json([
            'success' => true,
            'chat' => $chat
        ], 200);
    }

    public function destroy($workspace_id, $chatId): JsonResponse
    {
        $chat = Chat::findOrFail($chatId);

        if ($chat->user_id !== auth()->id() || $chat->workspace_id !== $workspace_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $chatId = $chat->id;
        $userId = auth()->id();

        $chat->messages()->delete();
        $chat->delete();

        Cache::forget("user:{$userId}:chats:last20");
        Cache::forget("chat:{$chatId}:messages:page:1:limit:50");

        return response()->json(['success' => true]);
    }
}
