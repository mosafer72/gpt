<?php

namespace App\Http\Controllers\Api;

use App\Application\Pipelines\WorkspacePipelineFactory;
use App\Http\Controllers\Controller;
use App\Application\Context\ChatContext;
use App\Jobs\StoreMessageJob;
use App\Models\Chat;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

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

        $perPage = (int) $request->input('per_page', 20);
        $page = (int) $request->input('page', 1);

        $cacheKey = "chat:{$chat->id}:messages:{$page}:{$perPage}";

        $messages = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($chat, $perPage, $page) {

                $paginator = $chat->messages()
                    ->orderBy('created_at', 'asc')
                    ->paginate(
                        $perPage,
                        ['*'],
                        'page',
                        $page
                    );

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
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // بررسی و گرفتن Workspace فعال کاربر در همان ابتدا
        $workspace = $user->workspaces()
            ->where('is_active', true)
            ->where('workspaces.id', $request->workspace_id) // بررسی دقیق شناسه ورک‌پیس
            ->first();

        if (!$workspace) {
            return response()->json([
                'success' => false,
                'error' => 'No active workspace',
                'message' => 'workspace فعالی برای شما یافت نشد.'
            ], 403);
        }

        $request->validate([
            'message' => 'nullable|string',
            'chat_id' => 'nullable|exists:chats,id',
            'model_id' => 'required|exists:ai_models,id',
            'image' => 'nullable|image|max:5120',
            'pdf' => 'nullable|file|mimes:pdf|max:20480',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'exists:documents,id',
            'paper_id' => 'nullable|exists:papers,id'
        ]);

        $content = trim($request->message ?? '');

        // اگر داکیومنت انتخاب شده اما متن پیام خالی است
        if (empty($content)) {
            if (!empty($request->document_ids) || $request->hasFile('pdf') || $request->hasFile('image')) {
                return response()->stream(
                    function () {
                        $noticeText = "داکیومنت انتخاب شد. لطفاً یک پیام یا سوال بنویسید.";

                        $chars = mb_str_split($noticeText);
                        foreach ($chars as $char) {
                            echo "data: " . json_encode([
                                'content' => $char
                            ], JSON_UNESCAPED_UNICODE) . "\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                            usleep(25000);
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

            return response()->json([
                'success' => false,
                'error' => 'Empty message',
                'message' => 'متن پیام نمی‌تواند خالی باشد.'
            ], 422);
        }

        $subscription = $workspace
            ->activeSubscription()
            ->first();

        if (!$subscription) {
            return response()->json([
                'error' => 'No active subscription'
            ], 403);
        }

        $subscription->load('workspacePlan.models');

        $aiModel = $subscription->workspacePlan
            ?->models
                ?->firstWhere('id', (int) $request->model_id);

        if (!$aiModel) {
            return response()->json([
                'success' => false,
                'error' => 'Model unavailable',
                'message' => "مدل انتخاب شده (ID: {$request->model_id}) در اشتراک فعلی شما در دسترس نیست."
            ], 403);
        }

        if ($request->chat_id) {
            $chat = Chat::where('id', $request->chat_id)
                ->where('workspace_id', $workspace->id)
                ->first();

            if (!$chat) {
                return response()->json([
                    'error' => 'Chat not found'
                ], 404);
            }
        } else {
            $chat = Chat::create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
                'title' => mb_substr($content, 0, 50)
            ]);
        }


        Cache::forget("user:{$user->id}:chats:last20");

        $documentIds = array_map('strval', $request->document_ids ?? []);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat-images', 'public');
        }

        $pdfPath = null;
        if ($request->hasFile('pdf')) {
            $pdfPath = $request->file('pdf')->store('chat-pdf', 'public');
        }

        $context = new ChatContext(
            userId: (string) $user->id,
            conversationId: (string) $chat->id,
            currentMessage: $content,
            model: $aiModel->key
        );
        $context->setWorkspace($workspace);

        $context->setMetadata([
            'workspace_id' => $workspace->id,
            'model_id' => $aiModel->id,
            'document_ids' => $documentIds,
            'paper_id' => $request->paper_id,
            'attachments' => [
                'image' => $imagePath,
                'pdf' => $pdfPath
            ]
        ]);

        $pipeline = $this->pipelineFactory->make($workspace["type"]);


        return response()->stream(
            function () use ($pipeline, $context) {
                $pipeline->stream(
                    $context,
                    function ($chunk) {
                        echo "data: " . json_encode(
                            $chunk,
                            JSON_UNESCAPED_UNICODE
                        ) . "\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }

                        flush();
                    }
                );

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
    public function show($id, $chat)
    {
        $chat = Chat::with('messages')->findOrFail($chat);

        if ($chat->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Forbidden'
            ], 403);
        }

        return response()->json([
            'chat' => $chat
        ]);
    }

    public function destroy($workspace_id,$chat): JsonResponse
    {
        $chat=Chat::findOrFail($chat);

        if ($chat->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Forbidden'
            ], 403);
        }

        $chatId = $chat->id;
        $userId = auth()->id();

        $chat->messages()->delete();
        $chat->delete();

        Cache::forget(
            "user:{$userId}:chats:last20"
        );

        Cache::forget(
            "chat:{$chatId}:messages:page:1:limit:50"
        );

        return response()->json([
            'success' => true
        ]);
    }
}
