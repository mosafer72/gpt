<?php

namespace App\Application\Pipelines\Academic;

use App\Application\Context\ChatContext;
use App\Application\Pipelines\PromptPipeline;
use App\Application\Services\WorkspaceAccessService;
use App\Application\Services\TokenUsageService;
use App\Application\Services\WorkspaceService;
use App\Application\Services\Documents\DocumentContextBuilder;
use App\Application\Services\Documents\AnswerEvidenceBuilder;
use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\DocumentRetriever;
use App\Domain\Contracts\WorkspacePipeline;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;
use App\Domain\Message\DTOs\MessageDTO;
use App\Infrastructure\LLM\GapGPTProvider;
use App\Jobs\StoreMessageJob;
use App\Models\DocumentChunk; // افزودن مدل DocumentChunk برای کوئری مستقیم کامل
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StudentWorkspacePipeline implements WorkspacePipeline
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected ConversationLoader $conversationLoader,
        protected DocumentRetriever $documentRetriever,
        protected DocumentContextBuilder $documentContextBuilder,
        protected AnswerEvidenceBuilder $answerEvidenceBuilder,
        protected GapGPTProvider $gapgptprovider,
        protected WorkspaceAccessService $workspaceAccessService,
        protected TokenUsageService $tokenUsageService,
        protected WorkspaceService $workspaceService,
        protected PromptPipeline $promptPipeline,
    ) {
    }

    public function handle(ChatContext $context): ChatContext
    {

        $user = $this->prepareContext($context);

        $this->prepareDocuments($context);

        $this->promptPipeline->build($context);

        $availableModels = $this->getAvailableModels($context);

        $response = null;
        $successfulModel = null;
        $lastException = null;

        foreach ($availableModels as $model) {
            try {
                $this->workspaceAccessService->check(
                    user: $user,
                    modelKey: $model->key
                );

                $request = $this->createRequest($context, $model);
                $response = $this->gapgptprovider->chat($request);

                $successfulModel = $model;
                $context->model = $model->key;
                break;
            } catch (\Throwable $e) {
                \Log::warning("Model {$model->key} failed in handle(). Trying next model...", [
                    'error' => $e->getMessage(),
                    'workspace_id' => $this->getWorkspaceId($context)
                ]);
                $lastException = $e;
            }
        }

        if (!$response || !$successfulModel) {
            \Log::error("All models failed in StudentWorkspacePipeline::handle()", [
                'error' => $lastException?->getMessage()
            ]);
            throw new \RuntimeException("تمام مدل‌های این پلن با خطا مواجه شدند. لطفاً دوباره تلاش کنید.", 0, $lastException);
        }

        $this->tokenUsageService->consume(
            workspace: $context->workspace,
            model: $successfulModel,
            response: $response,
            conversationId: $context->conversationId,
            userId: $user->id
        );

        $context->setAssistantResponse(
            $response->content,
            [
                'prompt_tokens' => $response->usage->inputTokens,
                'completion_tokens' => $response->usage->outputTokens,
                'total_tokens' => $response->usage->totalTokens
            ]
        );

        // $this->storeMessage($context, $response);

        return $context;
    }

    public function stream(ChatContext $context, callable $send): void
    {
        $user = $this->prepareContext($context);

        $this->prepareDocuments($context);

        $this->promptPipeline->build($context);

        $availableModels = $this->getAvailableModels($context);

        $fullContent = '';
        $successfulModel = null;
        $streamSuccess = false;

        foreach ($availableModels as $model) {
            try {
                $this->workspaceAccessService->check(
                    user: $user,
                    modelKey: $model->key
                );

                $request = $this->createRequest($context, $model);

                $currentFull = '';
                $chunkStreamStarted = false;
                $modelFailedMidStream = false;

                $streamGenerator = $this->gapgptprovider->stream($request);

                foreach ($streamGenerator as $chunk) {

                    $content = null;

                    if (is_array($chunk)) {
                        $content = $chunk['choices'][0]['delta']['content'] ?? null;
                    }

                    if (is_object($chunk)) {
                        $content = $chunk->choices[0]->delta->content ?? null;
                    }


                    if ($content !== null && $content !== '') {

                        $currentFull .= $content;
                        $chunkStreamStarted = true;

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


                    $finish = null;

                    if (is_array($chunk)) {
                        $finish = $chunk['choices'][0]['finish_reason'] ?? null;
                    }

                    if (is_object($chunk)) {
                        $finish = $chunk->choices[0]->finish_reason ?? null;
                    }


                    if ($finish === 'stop') {
                        break;
                    }
                }

                // اگر استریم با موفقیت شروع شد و به خطا نخورد، کار تمام است
                if ($chunkStreamStarted && !$modelFailedMidStream && !empty($currentFull)) {
                    $fullContent = $currentFull;
                    $successfulModel = $model;
                    $context->model = $model->key;
                    $streamSuccess = true;

                    // خروج ۱۰۰٪ قطعی از حلقه مدل‌ها (دیگر سراغ مدل بعدی نمی‌رود)
                    break;
                }

                if ($modelFailedMidStream && !empty($currentFull)) {
                    // اگر وسط کار خطا داد اما مقداری متن ارسال شده بود، پردازش را حفظ کن و ادامه نده
                    $fullContent = $currentFull;
                    $successfulModel = $model;
                    $context->model = $model->key;
                    $streamSuccess = true;
                    break;
                }

            } catch (\Throwable $e) {
                \Log::warning("Model {$model->key} failed. Retrying next model...", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!$streamSuccess || !$successfulModel) {
            \Log::error("All available models in subscription plan failed during stream execution.");

            $send([
                'error' => 'تمام سرویس‌های هوش مصنوعی در حال حاضر با محدودیت یا خطا مواجه هستند. لطفاً لحظاتی بعد مجدداً تلاش کنید.'
            ]);
            return;
        }

        $response = new ChatResponse(
            content: $fullContent,
            usage: new Usage(
                inputTokens: 0,
                outputTokens: 0,
                totalTokens: 0
            )
        );

        $this->tokenUsageService->consume(
            workspace: $context->workspace,
            model: $successfulModel,
            response: $response,
            conversationId: $context->conversationId,
            userId: $user->id
        );

        // $this->storeMessage($context, $response);

        $send([
            'done' => true
        ]);
    }
    protected function getAvailableModels(ChatContext $context): array
    {
        $workspace = $context->workspace;

        if (!$workspace) {
            return [];
        }

        $subscription = $workspace->activeSubscription()->first();

        if (!$subscription) {
            return [];
        }

        $subscription->loadMissing('workspacePlan.models');

        $allModels = $subscription->workspacePlan?->models ?? collect();

        if ($allModels->isEmpty()) {
            return [];
        }

        $requestedModelKey = $context->model;

        $primaryModel = $allModels->firstWhere('key', $requestedModelKey)
            ?? $allModels->firstWhere('id', $requestedModelKey);

        if ($primaryModel) {

            $fallbackModels = $allModels
                ->reject(fn($m) => $m->id === $primaryModel->id)
                ->values();

            return array_merge(
                [$primaryModel],
                $fallbackModels->all()
            );
        }

        return $allModels->all();
    }
    protected function prepareDocuments(ChatContext $context): void
    {
        $documents = $this->retrieveDocuments($context);

        $documentContext = $this->documentContextBuilder->build($documents);

        $context->setDocuments($documents);

        $context->addMetadata('document_context', $documentContext);
        $context->addMetadata('document_text', $documentContext['text'] ?? '');

        $evidence = $this->answerEvidenceBuilder->build(
            $documentContext['sections'] ?? []
        );

        $context->addMetadata('evidence', $evidence);
        $context->addMetadata('evidence_text', $this->answerEvidenceBuilder->toPromptText($evidence));
    }

    /**
     * دریافت **تمام** چانک‌های داکیومنت‌های انتخاب‌شده بدون محدودیتی در تعداد محدود چانک
     */
    protected function retrieveDocuments(ChatContext $context): array
    {
        $documentIds = $context->getMetadata('document_ids', []);
        $documentId = $context->getMetadata('document_id');

        if (empty($documentIds) && $documentId) {
            $documentIds = [(string) $documentId];
        }

        if (empty($documentIds)) {
            return [];
        }

        // کوئری مستقیم روی DocumentChunk برای دریافت تمام محتوای داکیومنت‌ها
        $chunks = DocumentChunk::whereIn('document_id', $documentIds)
            ->orderBy('document_id')
            ->orderBy('chunk_index') // یا page_number بر اساس فیلد دیتابیس شما
            ->get();

        return $chunks->map(function ($chunk) {
            return [
                'id' => $chunk->id,
                'document_id' => $chunk->document_id,
                'content' => $chunk->content,
                'chunk_index' => $chunk->chunk_index ?? 0,
                'metadata' => $chunk->metadata ?? []
            ];
        })->toArray();
    }

    protected function createRequest(ChatContext $context, mixed $model): ChatRequest
    {
        $messages = [];

        foreach ($context->prompt as $message) {
            $messages[] = new ChatMessage(
                role: $message['role'],
                content: $message['content']
            );
        }

        $modelName = is_object($model) ? ($model->model_name ?? $model->key) : $context->model;

        return new ChatRequest(
            messages: $messages,
            model: $modelName,
            temperature: 0.3,
            maxTokens: 100000
        );
    }

    protected function prepareContext(ChatContext $context): User
    {
        if (!$context->conversationId) {
            $conversation = $this->conversationRepository->create(
                userId: $context->userId,
                title: mb_substr($context->currentMessage, 0, 100)
            );

            $context->conversationId = $conversation->id;
        }

        $messages = $this->conversationLoader->load(
            conversationId: $context->conversationId,
            limit: 10
        );

        $context->setMessages(
            collect($messages)->map(function ($message) use ($context) {
                $metadata = $message->metadata ?? [];

                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }

                if (!is_array($metadata)) {
                    $metadata = [];
                }

                return new MessageDTO(
                    id: (string) ($message->id ?? uniqid()),
                    conversation: (string) $context->conversationId,
                    user_id: $message->user_id ?? $context->userId,
                    role: $message->role,
                    content: $message->content,
                    type: $message->type ?? 'text',
                    token_count: (int) ($message->token_count ?? 0),
                    metadata: $metadata,
                    created_at: (string) ($message->created_at ?? now())
                );
            })->toArray()
        );

        $user = User::findOrFail($context->userId);

        $workspace = $this->workspaceService->current($user);

        if (!$workspace) {
            throw new \RuntimeException('Workspace not found');
        }

        $context->setWorkspace($workspace);

        return $user;
    }

    protected function storeMessage(ChatContext $context, ChatResponse $response): void
    {
        StoreMessageJob::dispatch([
            'id' => uniqid(),
            'conversation' => (string) $context->conversationId,
            'user_id' => $context->userId,
            'workspace_id' => $this->getWorkspaceId($context),
            'role' => 'assistant',
            'content' => $response->content,
            'token_count' => $response->usage->totalTokens,
            'metadata' => [
                'pipeline' => 'student_document',
                'used_model' => $context->model
            ],
            'created_at' => now()->toDateTimeString()
        ]);
    }

    protected function getWorkspaceId(ChatContext $context): mixed
    {
        if (is_array($context->workspace)) {
            return $context->workspace['id'] ?? null;
        }

        return $context->workspace?->id ?? $context->getMetadata('workspace_id');
    }
}
