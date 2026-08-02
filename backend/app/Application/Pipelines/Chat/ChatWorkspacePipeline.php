<?php

namespace App\Application\Pipelines\Chat;

use App\Application\AI\ImageGenerationService;
use App\Application\AI\ImageIntentDetector;
use App\Application\Capabilities\CapabilityResolver;
use App\Application\Context\ChatContext;
use App\Application\Pipelines\IntentPipeline;
use App\Application\Pipelines\MemoryPipeline;
use App\Application\Pipelines\PromptPipeline;
use App\Application\Services\TokenUsageService;
use App\Application\Services\WorkspaceAccessService;
use App\Application\Services\WorkspaceService;
use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\LlmProvider;
use App\Domain\Contracts\MessageStore;
use App\Domain\Contracts\SmallChatProvider;
use App\Domain\Contracts\WorkspacePipeline;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;
use App\Domain\Message\DTOs\MessageDTO;
use App\Infrastructure\LLM\GapGPTProvider;
use App\Jobs\StoreMemoryJob;
use App\Jobs\StoreMessageJob;
use App\Models\AiModel;
use App\Models\User;

class ChatWorkspacePipeline implements WorkspacePipeline
{
    protected bool $useSmallModel = true;

    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected ConversationLoader $conversationLoader,
        protected MemoryPipeline $memoryPipeline,
        protected IntentPipeline $intentPipeline,
        protected PromptPipeline $promptPipeline,
        protected LlmProvider $llmProvider,
        protected GapGPTProvider $gapgptprovider,
        protected CapabilityResolver $capabilityResolver,
        protected MessageStore $messageStore,
        protected WorkspaceAccessService $workspaceAccessService,
        protected TokenUsageService $tokenUsageService,
        protected ImageIntentDetector $imageIntentDetector,
        protected ImageGenerationService $imageGenerationService,
        protected WorkspaceService $workspaceService,
    ) {
    }

    public function handle(ChatContext $context): ChatContext
    {
        $user = $this->prepareContext($context);

        $this->checkWorkspaceAccess($context, $user);

        $this->runPreProcessing($context, $user);

        if ($this->isImageRequest($context)) {
            return $this->generateImageResponse($context);
        }

        $this->promptPipeline->build($context);

        $response = $this->generateResponse($context, $user);

        $this->afterResponse($context, $user, $response);

        return $context;
    }

 public function stream(ChatContext $context, callable $send): void
    {
        if (session()->isStarted()) {
            session()->save();
        }
        $user = $this->prepareContext($context);
        $this->checkWorkspaceAccess($context, $user);
        $this->runPreProcessing($context, $user);

        $this->promptPipeline->build($context);

        $model = $this->workspaceAccessService->resolveModel(
            $context->workspace,
            $context->model
        );

        $request = $this->createRequest($context, $user, $model);
        \Log::info("all_request", $request->toArray());
        $stream = $this->gapgptprovider->stream($request);

        $fullContent = '';
        $promptTokens = 0;
        $completionTokens = 0;

        // ✅ ذخیره پیام کاربر پیش از شروع استریم
        StoreMessageJob::dispatch([
            'id' => uniqid(),
            'conversation' => (string) $context->conversationId,
            'user_id' => (string) $user->id,
            'workspace_id' => $context->workspace->id,
            'role' => 'user',
            'content' => $context->currentMessage,
            'token_count' => (int) ceil(mb_strlen($context->currentMessage) / 4),
            'metadata' => [
                'model_id' => $context->model ?? null,
            ],
            'created_at' => now()->toDateTimeString()
        ]);

        foreach ($stream as $chunk) {
            if (is_object($chunk) && isset($chunk->usage)) {
                $promptTokens = $chunk->usage->promptTokens ?? $promptTokens;
                $completionTokens = $chunk->usage->completionTokens ?? $completionTokens;
            } elseif (is_array($chunk) && isset($chunk['usage'])) {
                $promptTokens = $chunk['usage']['prompt_tokens'] ?? $promptTokens;
                $completionTokens = $chunk['usage']['completion_tokens'] ?? $completionTokens;
            }

            $content = null;

            if (is_object($chunk)) {
                $content = $chunk->choices[0]->delta->content ?? null;
                if (empty($content)) {
                    $content = $chunk->choices[0]->delta->reasoning ?? null;
                }
            } elseif (is_array($chunk)) {
                $content = $chunk['choices'][0]['delta']['content'] ?? null;
                if (empty($content)) {
                    $content = $chunk['choices'][0]['delta']['reasoning'] ?? null;
                }
            }

            if ($content !== null && $content !== '') {
                $fullContent .= $content;

                $send([
                    'choices' => [
                        [
                            'delta' => [
                                'content' => $content
                            ]
                        ]
                    ]
                ]);
            }

            $error = is_object($chunk) ? ($chunk->error ?? null) : ($chunk['error'] ?? null);

            if ($error) {
                $send(['error' => $error]);
                return;
            }
        }

        if ($fullContent !== '') {
            if ($promptTokens === 0) {
                $promptTokens = (int) ceil(mb_strlen($context->currentMessage) / 4);
            }

            if ($completionTokens === 0) {
                $completionTokens = (int) ceil(mb_strlen($fullContent) / 4);
            }

            $totalTokens = $promptTokens + $completionTokens;

            $responseDTO = new \App\Domain\LLM\ChatResponse(
                content: $fullContent,
                usage: new \App\Domain\LLM\Usage(
                    inputTokens: $promptTokens,
                    outputTokens: $completionTokens,
                    totalTokens: $totalTokens
                )
            );

            // ✅ اضافه شدن کسر توکن در حالت استریم (مشابه متد عادی)
            $this->tokenUsageService->consume(
                workspace: $context->workspace,
                model: $model,
                response: $responseDTO,
                conversationId: $context->conversationId
            );

            StoreMessageJob::dispatch([
                'id' => uniqid(),
                'conversation' => (string) $context->conversationId,
                'user_id' => $context->userId,
                'workspace_id' => $context->workspace->id,
                'role' => 'assistant',
                'content' => $fullContent,
                'token_count' => $completionTokens,
                'metadata' => [
                    'pipeline' => 'chat',
                    'model' => $model->key,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $totalTokens
                ],
                'created_at' => now()->toDateTimeString()
            ]);

            if ($this->capabilityResolver->enabled($user, 'memory')) {
                StoreMemoryJob::dispatch(
                    userId: $context->userId,
                    message: $context->currentMessage
                );
            }
        }

        $send(['done' => true]);
    }

    protected function checkWorkspaceAccess(ChatContext $context, User $user): void
    {
        $this->workspaceAccessService->check(
            user: $user,
            modelKey: $context->model,
            estimatedTokens: 0,
            workspaceId: $context->workspace->id ?? null
        );
    }

    protected function runPreProcessing(ChatContext $context, User $user): void
    {
        $capabilities = $this->capabilityResolver->all($user);

        $context->addMetadata(
            'capabilities',
            $capabilities
        );

        if (($capabilities['memory'] ?? false) && mb_strlen(trim($context->currentMessage)) > 5) {
            $this->loadMemory($context);
        }
    }

    protected function loadMemory(ChatContext $context): void
    {
        $memories = $this->memoryPipeline->retrieveMemories(
            userId: $context->userId,
            query: $context->currentMessage
        );

        $context->setMemories($memories);
    }

    protected function isImageRequest(ChatContext $context): bool
    {
        return (bool) $this->imageIntentDetector->detect(
            $context->currentMessage
        );
    }

    protected function generateImageResponse(ChatContext $context): ChatContext
    {
        $intent = $this->imageIntentDetector->detect(
            $context->currentMessage
        );
        $model = AiModel::where('type', 'image')
            ->where('is_active', true)
            ->firstOrFail();

        $result = $this->imageGenerationService->generate(
            prompt: $intent['prompt'],
            size: $intent['size'],
            model: $model
        );

        $context->setAssistantResponse(
            'تصویر ایجاد شد',
            [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0
            ]
        );

        $context->addMetadata('type', 'image');
        $context->addMetadata('image_url', $result['url']);
        $context->addMetadata('model', $model->key);

        $this->messageStore->store(
            new MessageDTO(
                id: uniqid(),
                conversation: (string) $context->conversationId,
                user_id: $context->userId,
                role: 'assistant',
                type: 'image',
                content: 'تصویر ایجاد شد',
                image_url: $result['url'],
                token_count: 0,
                metadata: [
                    'model' => $model->key
                ],
                created_at: now()->toDateTimeString()
            )
        );

        return $context;
    }

    protected function generateResponse(ChatContext $context, User $user): ChatResponse
    {
        $model = $this->workspaceAccessService->resolveModel(
            $context->workspace,
            $context->model
        );

        $request = $this->createRequest(
            $context,
            $user,
            $model
        );

        $response = $this->useSmallModel
            ? $this->gapgptprovider->chat($request)
            : $this->llmProvider->chat($request);

        $context->setAssistantResponse(
            $response->content,
            [
                'prompt_tokens' => $response->usage->inputTokens,
                'completion_tokens' => $response->usage->outputTokens,
                'total_tokens' => $response->usage->totalTokens
            ]
        );

        $this->tokenUsageService->consume(
            workspace: $context->workspace,
            model: $model,
            response: $response,
            conversationId: $context->conversationId
        );

        return $response;
    }

    protected function afterResponse(ChatContext $context, User $user, ChatResponse $response): void
    {
        StoreMessageJob::dispatch([
            'id' => uniqid(),
            'conversation' => (string) $context->conversationId,
            'user_id' => $context->userId,
            'workspace_id' => $context->workspace->id,
            'role' => 'assistant',
            'content' => $response->content,
            'token_count' => $response->usage->totalTokens,
            'metadata' => [
                'pipeline' => 'chat'
            ],
            'created_at' => now()->toDateTimeString()
        ]);

        if ($this->capabilityResolver->enabled($user, 'memory')) {
            StoreMemoryJob::dispatch(
                userId: $context->userId,
                message: $context->currentMessage
            );
        }
    }

    protected function prepareContext(ChatContext $context): User
    {
        $this->ensureConversation($context);
        $this->loadConversationEntity($context);
        $this->loadConversation($context);

        $user = User::findOrFail($context->userId);
        $workspace = $this->workspaceService->current($user, $context->workspace->id);

        if (!$workspace) {
            throw new \RuntimeException('Workspace not found');
        }

        $context->setWorkspace($workspace);

        return $user;
    }

    protected function ensureConversation(ChatContext $context): void
    {
        if ($context->conversationId) {
            return;
        }

        $conversation = $this->conversationRepository->create(
            userId: $context->userId,
            title: mb_substr($context->currentMessage, 0, 100)
        );

        $context->conversationId = $conversation->id;
        $context->setConversation($conversation);
    }

    protected function loadConversationEntity(ChatContext $context): void
    {
        $conversation = $this->conversationRepository->find($context->conversationId);
        $context->setConversation($conversation);
    }

    protected function loadConversation(ChatContext $context): void
    {
        if (!$context->conversationId) {
            $context->setMessages([]);
            return;
        }

        $messages = $this->conversationLoader->load(
            conversationId: $context->conversationId,
            limit: 10
        );

        if (empty($messages)) {
            $context->setMessages([]);
            return;
        }

        $context->setMessages(
            collect($messages)
                ->map(function ($message) {
                    return new MessageDTO(
                        id: (string) ($message->id ?? uniqid()),
                        conversation: (string) ($message->conversation_id ?? ''),
                        user_id: $message->user_id ?? null,
                        role: $message->role,
                        content: $message->content,
                        type: $message->type ?? 'text',
                        token_count: (int) ($message->token_count ?? 0),
                        metadata: is_array($message->metadata) ? $message->metadata : [],
                        created_at: (string) ($message->created_at ?? now())
                    );
                })
                ->toArray()
        );
    }

    protected function createRequest(ChatContext $context, User $user, $model): ChatRequest
    {
        $messages = [];

        // ۱. افزودن حافظه‌ها (Memories) به عنوان پیام سیستمی در صورت وجود
        if (!empty($context->memories)) {
            $memoryText = collect($context->memories)
                ->pluck('content')
                ->implode("\n");

            $messages[] = ChatMessage::system("اطلاعاتی از حافظه کاربر:\n" . $memoryText);
        }

        $currentMessageContent = $context->currentMessage;
        $parsedContent = json_decode($currentMessageContent, true);

        // ۲. پردازش هوشمند پیام در صورت ساختار JSON (متن + تصویر یا فایل)
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsedContent)) {
            $textPart = !empty($parsedContent['text'])
                ? $parsedContent['text']
                : 'این پیوست را بررسی و تحلیل کن.';

            $hasImage = !empty($parsedContent['image']);
            $hasFile = !empty($parsedContent['file']);

            if ($hasImage || $hasFile) {
                $multimodalContent = [
                    [
                        'type' => 'text',
                        'text' => $textPart
                    ]
                ];

                // پیوست تصویر
                if ($hasImage) {
                    $multimodalContent[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $parsedContent['image']
                        ]
                    ];
                }

                // پیوست فایل/سند (PDF یا متن استخراج شده)
                if ($hasFile) {
                    $fileData = $parsedContent['file'];

                    // بررسی اینکه آیا فایل یک تصویر است یا سند متنی/PDF
                    if (str_starts_with($fileData, 'data:image/')) {
                        $multimodalContent[] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $fileData
                            ]
                        ];
                    } else {
                        // اگر سند است (مثل متن استخراج‌شده PDF)، به عنوان متن مکمل به پرامتر اضافه می‌شود
                        $multimodalContent[] = [
                            'type' => 'text',
                            'text' => "\n[محتوای سند پیوست‌شده]:\n" . $fileData
                        ];
                    }
                }

                $messages[] = ChatMessage::user($multimodalContent);
            } else {
                $messages[] = ChatMessage::user($textPart);
            }
        } else {
            // پیام متنی ساده
            $messages[] = ChatMessage::user($context->currentMessage);
        }

        // ۳. تنظیمات پلن و توکن‌های خروجی
        $subscription = $context->workspace->currentSubscription;
        $plan = $subscription?->workspacePlan;

        return new ChatRequest(
            messages: $messages,
            model: $model->key,
            temperature: 0.7,
            maxTokens: data_get(
                $plan?->capabilities,
                'max_tokens',
                4000
            )
        );
    }
    protected function estimateUsage(ChatRequest $request, string $response): Usage
    {
        $prompt = '';

        foreach ($request->messages as $message) {
            if (is_string($message->content)) {
                $prompt .= $message->content . "\n";
            } elseif (is_array($message->content)) {
                foreach ($message->content as $part) {
                    if (isset($part['text'])) {
                        $prompt .= $part['text'] . "\n";
                    }
                }
            }
        }

        $inputTokens = (int) ceil(mb_strlen($prompt) / 4);
        $outputTokens = (int) ceil(mb_strlen($response) / 4);

        return new Usage(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            totalTokens: $inputTokens + $outputTokens
        );
    }

    protected function handleIntentResponse(ChatContext $context): bool
    {
        $intent = $context->getMetadata('intent');

        if (!$intent) {
            return false;
        }

        if (($intent['type'] ?? null) === 'title') {
            $context->setAssistantResponse(
                'برای این درخواست نیاز به پردازش سند دارید.',
                [
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0
                ]
            );

            $context->addMetadata('direct_response', true);
            return true;
        }

        return false;
    }
}
