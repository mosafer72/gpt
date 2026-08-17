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
use App\Application\Services\TokenWalletService;
use App\Application\Services\WorkspaceAccessService;
use App\Application\Services\WorkspaceService;
use App\Application\Tools\WebSearchTool;
use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\LlmProvider;
use App\Domain\Contracts\MessageStore;
use App\Domain\Contracts\WorkspacePipeline;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;
use App\Domain\Message\DTOs\MessageDTO;
use App\Infrastructure\LLM\Cloudflare\CloudflareProvider;
use App\Infrastructure\LLM\GapGPTProvider;
use App\Jobs\StoreMemoryJob;
use App\Jobs\StoreMessageJob;
use App\Models\AiModel;
use App\Models\User;
use NunoMaduro\Collision\Provider;
use App\Application\Agents\AgentRuntime;
use App\Application\Agents\AgentDecision;

class ChatWorkspacePipeline implements WorkspacePipeline
{
    protected bool $useSmallModel = true;

    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected ConversationLoader $conversationLoader,
        protected MemoryPipeline $memoryPipeline,
        protected IntentPipeline $intentPipeline,
        protected PromptPipeline $promptPipeline,
        protected CloudflareProvider $CloudflareProvider,
        protected CapabilityResolver $capabilityResolver,
        protected MessageStore $messageStore,
        protected WorkspaceAccessService $workspaceAccessService,
        protected TokenUsageService $tokenUsageService,
        protected TokenWalletService $tokenWalletService,
        protected ImageIntentDetector $imageIntentDetector,
        protected ImageGenerationService $imageGenerationService,
        protected WorkspaceService $workspaceService,
        protected WebSearchTool $webSearchTool,
        protected AgentRuntime $agentRuntime,

    ) {
    }

    public function handle(ChatContext $context): ChatContext
    {
        $user = $this->prepareContext($context);

        $this->checkWorkspaceAccess($context, $user);

        $this->storeUserMessage($context, $user);

        $this->runPreProcessing($context, $user);


        $this->promptPipeline->build($context);

        $response = $this->generateResponse($context, $user);

        $this->afterResponse($context, $user, $response);

        return $context;
    }
    protected function handleImageGeneration(
        ChatContext $context,
        callable $send
    ): void {

        /*
         * ==========================================
         * 1. GET FINAL IMAGE PROMPT
         * ==========================================
         *
         * image_prompt باید قبلاً توسط Main Model
         * و با استفاده از conversation history ساخته شده باشد.
         */

        $prompt = $context->getMetadata('image_prompt');

        if (is_array($prompt)) {
            $prompt = $prompt['prompt']
                ?? $prompt['text']
                ?? '';
        }

        $prompt = trim((string) $prompt);


        /*
         * ==========================================
         * FALLBACK
         * ==========================================
         *
         * فقط برای جلوگیری از شکست pipeline.
         * در حالت عادی image_prompt باید توسط Main Model
         * تولید شده باشد.
         */

        if ($prompt === '') {

            $currentMessage = $context->currentMessage;

            if (is_array($currentMessage)) {
                $currentMessage = $currentMessage['text'] ?? '';
            }

            $prompt = trim((string) $currentMessage);
        }


        if ($prompt === '') {
            throw new \RuntimeException(
                'Image generation prompt is empty.'
            );
        }


        /*
         * ==========================================
         * LOG FINAL IMAGE PROMPT
         * ==========================================
         */

        \Log::info('IMAGE GENERATION START', [
            'conversation_id' => $context->conversationId,
            'user_id' => $context->userId,
            'prompt' => $prompt,
            'prompt_length' => mb_strlen($prompt),
            'history_limit' => $context->getMetadata('history_limit'),
            'decision' => $context->getMetadata('decision'),
        ]);


        /*
         * ==========================================
         * 2. INFORM FRONTEND
         * ==========================================
         */

        $send([
            'type' => 'image_generation',
            'status' => 'generating_image',
        ]);


        /*
         * ==========================================
         * 3. RESOLVE IMAGE MODEL
         * ==========================================
         */

        $imageModel = $context->workspace
            ->plan
            ->models()
            ->where('type', 'image')
            ->where('is_active', true)
            ->first();


        if (!$imageModel) {
            throw new \RuntimeException(
                'No active image generation model found.'
            );
        }


        /*
         * ==========================================
         * 4. GENERATE IMAGE
         * ==========================================
         */

        try {

            $result = $this->imageGenerationService->generate(
                prompt: $prompt,
                size: '1024x1024',
                model: $imageModel
            );

        } catch (\Throwable $e) {

            \Log::error('IMAGE GENERATION FAILED', [
                'conversation_id' => $context->conversationId,
                'user_id' => $context->userId,
                'model' => $imageModel->key,
                'prompt' => $prompt,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }


        /*
         * ==========================================
         * 5. GET IMAGE URL
         * ==========================================
         */

        $url = $result['url']
            ?? $result['image_url']
            ?? null;


        if (!$url) {

            \Log::error('IMAGE GENERATION EMPTY RESULT', [
                'conversation_id' => $context->conversationId,
                'model' => $imageModel->key,
                'result' => $result,
            ]);

            throw new \RuntimeException(
                'Image generation failed: image URL not returned.'
            );
        }


        /*
         * ==========================================
         * 6. CALCULATE TOKEN USAGE
         * ==========================================
         *
         * Image APIs معمولاً token usage استاندارد
         * مثل LLM برنمی‌گردانند.
         *
         * بنابراین فعلاً prompt را تقریبی محاسبه می‌کنیم.
         */

        $promptTokens = max(
            1,
            (int) ceil(mb_strlen($prompt) / 4)
        );


        $response = new ChatResponse(
            content: 'تصویر ایجاد شد',
            usage: new Usage(
                inputTokens: $promptTokens,
                outputTokens: 0,
                totalTokens: $promptTokens
            )
        );


        /*
         * ==========================================
         * 7. CONSUME TOKENS
         * ==========================================
         */

        $this->tokenUsageService->consume(
            workspace: $context->workspace,
            model: $imageModel,
            response: $response,
            conversationId: $context->conversationId,
            userId: $context->userId
        );


        /*
         * ==========================================
         * 8. STORE ASSISTANT IMAGE MESSAGE
         * ==========================================
         */

        $this->messageStore->store(
            new MessageDTO(
                id: uniqid(),

                conversation: (string) $context->conversationId,

                user_id: $context->userId,

                role: 'assistant',

                type: 'image',

                content: 'تصویر ایجاد شد',

                image_url: $url,

                token_count: $promptTokens,

                metadata: [
                    'pipeline' => 'image_generation',

                    'model' => $imageModel->key,

                    /*
                     * Prompt نهایی که واقعاً
                     * به Image Model ارسال شده.
                     */
                    'prompt' => $prompt,

                    'prompt_tokens' => $promptTokens,

                    'completion_tokens' => 0,

                    'total_tokens' => $promptTokens,

                    /*
                     * برای debugging و trace کردن pipeline
                     */
                    'user_request' => is_array($context->currentMessage)
                        ? ($context->currentMessage['text'] ?? '')
                        : (string) $context->currentMessage,

                    'history_limit' => $context->getMetadata(
                        'history_limit'
                    ),

                    'context_decision' => $context->getMetadata(
                        'decision'
                    ),
                ],

                created_at: now()->toDateTimeString()
            )
        );


        /*
         * ==========================================
         * 9. SEND IMAGE TO FRONTEND
         * ==========================================
         */

        $send([
            'type' => 'image',

            'status' => 'completed',

            'image' => $url,

            'image_url' => $url,

            'content' => 'تصویر ایجاد شد',
        ]);


        /*
         * ==========================================
         * 10. STREAM FINISHED
         * ==========================================
         */

        $send([
            'done' => true,
        ]);


        /*
         * ==========================================
         * FINAL LOG
         * ==========================================
         */

        \Log::info('IMAGE GENERATION COMPLETED', [
            'conversation_id' => $context->conversationId,
            'user_id' => $context->userId,
            'model' => $imageModel->key,
            'image_url' => $url,
            'prompt_tokens' => $promptTokens,
        ]);
    }
    public function stream(
        ChatContext $context,
        callable $send
    ): void {
        if (session()->isStarted()) {
            session()->save();
        }

        $user = $this->prepareContext(
            $context
        );

        $this->checkWorkspaceAccess(
            $context,
            $user
        );

        $this->storeUserMessage(
            $context,
            $user
        );

        $this->runPreProcessing(
            $context,
            $user
        );

        $model = $this->workspaceAccessService
            ->resolveModel(
                $context->workspace,
                $context->model
            );

        /*
         * ==========================================
         * AGENT
         * ==========================================
         */
        $this->runAgent(
            $context,
            $user,
            $model,
            $send
        );
    }

    protected function runAgent(
        ChatContext $context,
        User $user,
        $model,
        callable $send
    ): void {
        $this->agentRuntime->run(
            context: $context,
            user: $user,

            /*
             * ==========================================
             * AGENT BRAIN
             * ==========================================
             */
            decide: function (ChatContext $context, User $user) use ($model): string {

                return $this->decideAgentAction(
                    $context,
                    $user,
                    $model
                );
            },

            /*
             * ==========================================
             * ACTION EXECUTOR
             * ==========================================
             */
            execute: function (string $decision, ChatContext $context, User $user) use ($model, $send): array {

                return $this->executeAgentAction(
                    $decision,
                    $context,
                    $user,
                    $model,
                    $send
                );
            },

            /*
             * ==========================================
             * FINAL ANSWER
             * ==========================================
             */
            answer: function (ChatContext $context, User $user) use ($model, $send) {

                return $this->generateFinalAgentAnswer(
                    $context,
                    $user,
                    $model,
                    $send
                );
            }
        );
    }
    protected function executeAgentAction(
        string $decision,
        ChatContext $context,
        User $user,
        $model,
        callable $send
    ): array {

        return match ($decision) {

            '<answer/>' => $this->agentAnswer(
                $context,
                $user,
                $model,
                $send
            ),

            '<more_history/>' => $this->agentMoreHistory(
                $context
            ),

            '<web_search/>' => $this->agentWebSearch(
                $context
            ),

            '<image/>' => $this->agentImage(
                $context,
                $user,
                $model,
                $send
            ),

            default => throw new \RuntimeException(
                "Unknown agent decision: {$decision}"
            ),
        };
    }
    protected function agentWebSearch(
        ChatContext $context
    ): array {

        $query = $context->currentMessage;

        if (is_array($query)) {
            $query = $query['text'] ?? '';
        }

        $query = trim((string) $query);


        if ($query === '') {
            throw new \RuntimeException(
                'Web search query is empty.'
            );
        }


        $results = $this->webSearchTool->execute([
            'query' => $query,
        ]);


        $searchContext = collect($results)
            ->map(function ($item) {

                $content = $item['snippet']
                    ?? $item['content']
                    ?? '';

                return [
                    'title' => trim(
                        (string) ($item['title'] ?? '')
                    ),
                    'content' => trim(
                        preg_replace(
                            '/\s+/u',
                            ' ',
                            (string) $content
                        )
                    ),
                    'url' => $item['url'] ?? '',
                ];
            })
            ->filter(
                fn($item) =>
                $item['content'] !== ''
            )
            ->values()
            ->toArray();



        $context->addMetadata(
            'web_results',
            $searchContext
        );


        $context->addMetadata(
            'web_search_completed',
            true
        );


        $context->addMetadata(
            'force_next_action',
            '<answer/>'
        );


        $context->addMessage(
            new MessageDTO(
                id: uniqid(),
                conversation: (string) $context->conversationId,
                user_id: null,
                role: 'system',
                content:
                "WEB SEARCH RESULTS:\n\n"
                . json_encode(
                    $searchContext,
                    JSON_UNESCAPED_UNICODE |
                    JSON_PRETTY_PRINT
                ),
                token_count: 0,
                metadata: [
                    'tool' => 'web_search',
                    'query' => $query,
                ],
                created_at: now()->toDateTimeString()
            )
        );


        return [
            'continue' => true,
            'force_next_action' => '<answer/>'
        ];
    }
    protected function agentMoreHistory(
        ChatContext $context
    ): array {

        $currentLimit = (int) (
            $context->getMetadata('history_limit') ?? 0
        );

        $maxHistory = 100;

        $newLimit = min(
            $currentLimit + 10,
            $maxHistory
        );

        $messages = $this->loadHistoryForContext(
            $context,
            $newLimit
        );

        $context->setMessages($messages);

        $context->addMetadata(
            'history_limit',
            $newLimit
        );

        return [
            'continue' => true,
        ];
    }
    protected function decideAgentAction(
        ChatContext $context,
        User $user,
        $model
    ): string {

        $historyLimit = (int) (
            $context->getMetadata('history_limit') ?? 0
        );

        /*
         * 0 = فقط پیام فعلی کاربر
         * 10 = ده پیام قبلی
         * 20 = بیست پیام قبلی
         * ...
         */
        $messages = [];

        if ($historyLimit > 0) {
            $messages = $this->loadHistoryForContext(
                $context,
                $historyLimit
            );
        }
        $forced = $context->getMetadata(
            'force_next_action'
        );

        if ($forced) {
            return $forced;
        }
        $request = $this->createAgentDecisionRequest(
            $context,
            $messages,
            $model
        );

        $decision = $this->askContextDecision(
            $request,
            $model
        );

        $decision = AgentDecision::normalize($decision);

        $context->addMetadata(
            'decision',
            $decision
        );

        return $decision;
    }
    protected function createAgentDecisionRequest(
        ChatContext $context,
        array $messages,
        $model
    ): ChatRequest {

        $prompt = [];

        $prompt[] = ChatMessage::system(
            \App\Application\Prompts\SystemPrompt::contextDecision()
        );


        /*
         * ==========================================
         * FORCE ANSWER AFTER WEB SEARCH
         * ==========================================
         */

        $webSearchCompleted = $context->getMetadata(
            'web_search_completed'
        );


        if ($webSearchCompleted) {

            $prompt[] = ChatMessage::user(
                <<<'SEARCH_DONE'
WEB SEARCH HAS ALREADY BEEN COMPLETED.

Available web results are already in the context.

IMPORTANT:
- Do NOT return <web_search/>.
- Use the available search results.
- The next action MUST be <answer/>.
SEARCH_DONE
            );

        }


        /*
         * ==========================================
         * CURRENT USER REQUEST
         * ==========================================
         */

        $currentMessage = $context->currentMessage;

        if (is_array($currentMessage)) {
            $currentMessage = $currentMessage['text'] ?? '';
        }


        $prompt[] = ChatMessage::user(
            "CURRENT USER REQUEST:\n\n"
            . trim((string) $currentMessage)
        );


        /*
         * ==========================================
         * HISTORY
         * ==========================================
         */

        if (!empty($messages)) {

            $historyText = '';

            foreach ($messages as $message) {

                $historyText .=
                    strtoupper((string) $message->role)
                    . ":\n"
                    . trim((string) $message->content)
                    . "\n\n";
            }


            $prompt[] = ChatMessage::user(
                "CONVERSATION HISTORY:\n\n"
                . $historyText
            );
        }


        /*
         * ==========================================
         * WEB RESULTS
         * ==========================================
         */

        $webResults = $context->getMetadata(
            'web_results'
        );


        if (!empty($webResults)) {

            $prompt[] = ChatMessage::user(
                "WEB SEARCH RESULTS AVAILABLE:\n\n"
                . json_encode(
                    $webResults,
                    JSON_UNESCAPED_UNICODE |
                    JSON_PRETTY_PRINT
                )
            );

        }


        /*
         * ==========================================
         * ROUTING
         * ==========================================
         */

        $prompt[] = ChatMessage::user(
            <<<'ROUTING'
Choose exactly ONE action:

<answer/>
Answer the user using the current context.

<more_history/>
The user refers to missing previous conversation.

<web_search/>
Fresh external information is required and no search has been done.

<image/>
The user wants an image generated.

Rules:

- Return ONLY one action.
- Never explain.
- Never answer directly.
- If web search was already completed, ALWAYS return <answer/>.
- Do not request web search twice.

Output exactly one:

<answer/>
<more_history/>
<web_search/>
<image/>
ROUTING
        );


        return new ChatRequest(
            messages: $prompt,
            model: $model->key,
            temperature: 0,
            maxTokens: 10
        );
    }
    protected function agentAnswer(
        ChatContext $context,
        User $user,
        $model,
        callable $send
    ): array {

        $this->generateFinalAgentAnswer(
            $context,
            $user,
            $model,
            $send
        );

        return [
            'finish' => true,
        ];
    }
    protected function generateFinalAgentAnswer(
        ChatContext $context,
        User $user,
        $model,
        callable $send
    ): void {
        $this->promptPipeline->build(
            $context
        );

        $request = $this->createRequest(
            $context,
            $user,
            $model
        );

        $provider = $this->resolveProvider(
            $model
        );

        $stream = $provider->stream(
            $request
        );

        $this->processFinalStream(
            $stream,
            $context,
            $user,
            $model,
            $send
        );
    }
    protected function agentImage(
        ChatContext $context,
        User $user,
        $model,
        callable $send
    ): array {

        $currentMessage = $context->currentMessage;

        if (is_array($currentMessage)) {
            $currentMessage = $currentMessage['text'] ?? '';
        }

        $imagePrompt = $this->buildImagePrompt(
            $context,
            trim((string) $currentMessage),
            $model
        );

        $imagePrompt = trim($imagePrompt);

        if ($imagePrompt === '') {
            throw new \RuntimeException(
                'Main model returned an empty image prompt.'
            );
        }

        $context->addMetadata(
            'image_prompt',
            $imagePrompt
        );

        $this->handleImageGeneration(
            $context,
            $send
        );

        return [
            'finish' => true,
        ];
    }

    protected function processFinalStream(
        iterable $stream,
        ChatContext $context,
        User $user,
        $model,
        callable $send
    ): void {

        $fullContent = '';

        $promptTokens = 0;

        $completionTokens = 0;

        $sseBuffer = '';

        foreach ($stream as $rawChunk) {

            if (!is_string($rawChunk)) {
                continue;
            }

            if ($rawChunk === '') {
                continue;
            }

            $sseBuffer .= $rawChunk;

            while (
                ($separator = strpos(
                    $sseBuffer,
                    "\n\n"
                )) !== false
            ) {

                $event = substr(
                    $sseBuffer,
                    0,
                    $separator
                );

                $sseBuffer = substr(
                    $sseBuffer,
                    $separator + 2
                );

                $this->processSseEvent(
                    $event,
                    $context,
                    $user,
                    $model,
                    $send,
                    $fullContent,
                    $promptTokens,
                    $completionTokens
                );
            }
        }

        /*
         * Remaining incomplete SSE event.
         */
        if ($sseBuffer !== '') {

            $this->processSseEvent(
                $sseBuffer,
                $context,
                $user,
                $model,
                $send,
                $fullContent,
                $promptTokens,
                $completionTokens
            );
        }

        /*
         * Store response + token usage.
         */
        $this->finishStream(
            $context,
            $user,
            $model,
            $fullContent,
            $promptTokens,
            $completionTokens
        );

        $send([
            'done' => true
        ]);
    }
    protected function processSseEvent(
        string $event,
        ChatContext $context,
        User $user,
        $model,
        callable $send,
        string &$fullContent,
        int &$promptTokens,
        int &$completionTokens
    ): void {

        $lines = preg_split(
            "/\r\n|\r|\n/",
            $event
        );


        foreach ($lines as $line) {


            $line = rtrim($line);


            if (
                $line === ''
                ||
                !str_starts_with($line, 'data:')
            ) {
                continue;
            }


            $data = trim(
                substr($line, 5)
            );


            if ($data === '[DONE]') {
                continue;
            }


            $json = json_decode(
                $data,
                true
            );


            if (!is_array($json)) {
                continue;
            }


            if (isset($json['usage'])) {

                $promptTokens =
                    $json['usage']['prompt_tokens']
                    ??
                    $promptTokens;


                $completionTokens =
                    $json['usage']['completion_tokens']
                    ??
                    $completionTokens;
            }



            $content = data_get(
                $json,
                'response'
            );


            if ($content === null) {

                $content = data_get(
                    $json,
                    'choices.0.delta.content',
                    ''
                );

            }



            if ($content === '') {
                continue;
            }



            if (
                $content === '<hc/>'
                ||
                $content === '<mo/>'
                ||
                $content === '<ws/>'
            ) {
                continue;
            }



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
    }
    protected function finishStream(
        ChatContext $context,
        User $user,
        $model,
        string $content,
        int $promptTokens,
        int $completionTokens
    ): void {
        if ($promptTokens === 0) {

            $promptTokens = (int) ceil(
                mb_strlen($context->currentMessage) / 4
            );

        }


        if ($completionTokens === 0) {

            $completionTokens = (int) ceil(
                mb_strlen($content) / 4
            );

        }


        $response = new ChatResponse(
            content: $content,
            usage: new Usage(
                inputTokens: $promptTokens,
                outputTokens: $completionTokens,
                totalTokens: $promptTokens + $completionTokens
            )
        );


        $this->tokenUsageService->consume(
            workspace: $context->workspace,
            model: $model,
            response: $response,
            conversationId: $context->conversationId,
            userId: $user->id
        );


        $wallet = $this->tokenWalletService->get(
            $context->workspace
        );





        StoreMessageJob::dispatch([
            'id' => uniqid(),
            'conversation' => (string) $context->conversationId,
            'user_id' => $user->id,
            'workspace_id' => $context->workspace->id,
            'role' => 'assistant',
            'content' => $content,
            'token_count' => $completionTokens,
            'metadata' => [
                'pipeline' => 'chat',
                'model' => $model->key,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens
            ],
            'created_at' => now()->toDateTimeString()
        ]);


        if ($this->capabilityResolver->enabled($user, 'memory')) {

            StoreMemoryJob::dispatch(
                userId: $user->id,
                message: $context->currentMessage
            );

        }
    }



    protected function storeUserMessage(
        ChatContext $context,
        User $user
    ): void {
        StoreMessageJob::dispatch([
            'id' => uniqid(),
            'conversation' => (string) $context->conversationId,
            'user_id' => $user->id,
            'workspace_id' => $context->workspace->id,
            'role' => 'user',
            'content' => $context->currentMessage,
            'token_count' => (int) ceil(
                mb_strlen($context->currentMessage) / 4
            ),
            'metadata' => [
                'model_id' => $context->model
            ],
            'created_at' => now()->toDateTimeString()
        ]);
    }



    protected function checkWorkspaceAccess(
        ChatContext $context,
        User $user
    ): void {
        $this->workspaceAccessService->check(
            user: $user,
            modelKey: $context->model,
            estimatedTokens: 0,
            workspaceId: $context->workspace->id ?? null
        );
    }



    protected function runPreProcessing(
        ChatContext $context,
        User $user
    ): void {
        $capabilities = $this->capabilityResolver->all(
            $user
        );


        $context->addMetadata(
            'capabilities',
            $capabilities
        );


        if (
            ($capabilities['memory'] ?? false)
            &&
            mb_strlen(trim($context->currentMessage)) > 10
        ) {

            $this->loadMemory($context);

        }
    }
    private function updateChatStatus(
        int $chatId,
        string $status
    ): void {

        cache()->put(
            "chat:{$chatId}:status",
            $status,
            now()->addMinutes(10)
        );
    }



    protected function loadMemory(
        ChatContext $context
    ): void {
        $memories = $this->memoryPipeline->retrieveMemories(
            userId: $context->userId,
            query: $context->currentMessage
        );


        $context->setMemories(
            $memories
        );
    }

    protected function generateResponse(
        ChatContext $context,
        User $user
    ): ChatResponse {
        $model = $this->workspaceAccessService->resolveModel(
            $context->workspace,
            $context->model
        );


        $request = $this->createRequest(
            $context,
            $user,
            $model
        );


        $provider = $this->llmProvider;

        $response = $provider->stream(
            $request
        );

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
            conversationId: $context->conversationId,
            userId: $user->id
        );


        return $response;
    }



    protected function afterResponse(
        ChatContext $context,
        User $user,
        ChatResponse $response
    ): void {
        StoreMessageJob::dispatch([
            'id' => uniqid(),
            'conversation' => (string) $context->conversationId,
            'user_id' => $user->id,
            'workspace_id' => $context->workspace->id,
            'role' => 'assistant',
            'content' => $response->content,
            'token_count' => $response->usage->outputTokens,
            'metadata' => [
                'pipeline' => 'chat',
                'model' => $context->model
            ],
            'created_at' => now()->toDateTimeString()
        ]);


        if (
            $this->capabilityResolver
                ->enabled(
                    $user,
                    'memory'
                )
        ) {

            StoreMemoryJob::dispatch(
                userId: $context->userId,
                message: $context->currentMessage
            );

        }
    }



    protected function prepareContext(
        ChatContext $context
    ): User {

        $this->ensureConversation(
            $context
        );

        $this->loadConversationEntity(
            $context
        );

        /*
         * DO NOT load conversation history here.
         *
         * History will be resolved dynamically
         * by resolveRequiredHistory().
         */

        $context->setMessages([]);

        $context->setDocuments([]);

        $user = User::findOrFail(
            $context->userId
        );

        $workspace = $this->workspaceService->current(
            $user,
            $context->workspace->id
        );

        if (!$workspace) {

            throw new \RuntimeException(
                'Workspace not found'
            );
        }

        $context->setWorkspace(
            $workspace
        );

        return $user;
    }



    protected function ensureConversation(
        ChatContext $context
    ): void {
        if ($context->conversationId) {

            return;

        }


        $conversation = $this->conversationRepository->create(
            userId: $context->userId,
            title: mb_substr(
                $context->currentMessage,
                0,
                100
            )
        );


        $context->conversationId = $conversation->id;


        $context->setConversation(
            $conversation
        );
    }



    protected function loadConversationEntity(
        ChatContext $context
    ): void {
        if (!$context->conversationId) {

            return;

        }


        $conversation = $this->conversationRepository->find(
            $context->conversationId
        );


        if ($conversation) {

            $context->setConversation(
                $conversation
            );

        }
    }
    protected function loadHistoryForContext(
        ChatContext $context,
        int $limit
    ): array {

        if ($limit <= 0) {
            return [];
        }


        if (!$context->conversationId) {
            return [];
        }


        $messages = $this->conversationLoader->load(
            conversationId: $context->conversationId,
            limit: $limit
        );


        if (empty($messages)) {
            return [];
        }


        return $this->normalizeHistory(
            $messages,
            $context
        );
    }
    protected function isSimpleMessage(
        string $message
    ): bool {

        $message = trim($message);


        if ($message === '') {
            return true;
        }


        /*
         * پیام‌های کوتاه مکالمه‌ای
         */
        $simpleMessages = [
            'سلام',
            'سلام خوبی',
            'خوبی',
            'چه خبر',
            'مرسی',
            'ممنون',
            'خداحافظ',
        ];


        if (in_array($message, $simpleMessages)) {
            return true;
        }


        /*
         * خیلی کوتاه‌ها
         */
        return mb_strlen($message) < 15;
    }
    protected function normalizeHistory(
        array $messages,
        ChatContext $context
    ): array {

        $result = [];

        foreach ($messages as $message) {

            $content = $this->normalizeMessageContent(
                (string) $message->content
            );

            $role = $message->role;

            if (
                $role === 'assistant'
                &&
                (
                    str_contains(
                        $content,
                        'لطفاً سند مورد نظر خود را آپلود کنید'
                    )
                    ||
                    str_contains(
                        $content,
                        'برای این درخواست نیاز به پردازش سند دارید'
                    )
                )
            ) {
                continue;
            }

            if (
                str_contains(
                    $content,
                    '[محتوای سند پیوست‌شده]:'
                )
            ) {

                $parts = explode(
                    '[محتوای سند پیوست‌شده]:',
                    $content,
                    2
                );

                $content = trim(
                    $parts[0]
                );

                if ($content === '') {
                    $content = 'سند پیوست شده';
                }
            }

            $result[] = new MessageDTO(
                id: (string) (
                    $message->id
                    ?? uniqid()
                ),

                conversation: (string) (
                    $message->conversation_id
                    ?? $context->conversationId
                    ?? ''
                ),

                user_id: $message->user_id ?? null,

                role: $role,

                content: $content,

                type: $message->type ?? 'text',

                token_count: (int) (
                    $message->token_count ?? 0
                ),

                metadata: is_array($message->metadata)
                ? $message->metadata
                : [],

                created_at: (string) (
                    $message->created_at
                    ?? now()
                )
            );
        }

        return $result;
    }
    protected function createContextDecisionRequest(
        ChatContext $context,
        array $messages,
        $model
    ): ChatRequest {


        $prompt = [];


        $prompt[] = ChatMessage::system(
            \App\Application\Prompts\SystemPrompt::contextDecision()
        );



        $currentMessage = $context->currentMessage;


        if (is_array($currentMessage)) {

            $currentMessage =
                $currentMessage['text'] ?? '';

        }



        $prompt[] = ChatMessage::user(
            "CURRENT USER REQUEST:\n\n"
            . trim($currentMessage)
        );



        if (!empty($messages)) {


            $historyText = '';

            foreach ($messages as $message) {

                $historyText .=
                    strtoupper($message->role)
                    . ":\n"
                    . $message->content
                    . "\n\n";

            }



            $prompt[] = ChatMessage::user(
                "CONVERSATION HISTORY:\n\n"
                . $historyText
            );

        }



        /*
         * Tool routing instruction
         */
        $prompt[] = ChatMessage::user(
            <<<'ROUTING'
Available routes:
<hc/>
The current context is enough.
<mo/>
More conversation history is required.
<ws/>
Fresh information from the internet is required.
<image/>
The user wants an image to be generated.
Examples:
User:
"عکس یه گربه برام بکش"
Return:
<image/>
User:
"یه عکس از یه ماشین بساز"
Return:
<image/>
User:
"یک تصویر کارتونی از یک ربات تولید کن"
Return:
<image/>
User:
"قیمت بیت کوین الان چنده؟"
Return:
<ws/>
User:
"آخرین نسخه Laravel چیست؟"
Return:
<ws/>
User:
"همون کد قبلی رو اصلاح کن"
Return:
<mo/>
User:
"PHP چیست؟"
Return:
<hc/>
IMPORTANT:
Image generation requests must ALWAYS return:
<image/>
Do NOT return <ws/> for image generation.
Return ONLY ONE route:
<hc/>
<mo/>
<ws/>
<image/>
ROUTING
        );

        $prompt[] = ChatMessage::user(
            "Output only:

<hc/>
<mo/>
<ws/>
<image/>"
        );



        return new ChatRequest(
            messages: $prompt,
            model: $model->key,
            temperature: 0,
            maxTokens: 10
        );
    }
    protected function askContextDecision(
        ChatRequest $request,
        $model
    ): string {

        $provider = $this->resolveProvider($model);

        $stream = $provider->stream($request);

        $content = trim(
            $this->collectStreamContent($stream)
        );

        $content = trim(
            $content,
            " \t\n\r\0\x0B`\"'"
        );

        $allowedDecisions = [
            '<answer/>',
            '<more_history/>',
            '<web_search/>',
            '<image/>',
        ];

        if (in_array($content, $allowedDecisions, true)) {
            return $content;
        }

        foreach ($allowedDecisions as $decision) {

            if (str_contains($content, $decision)) {
                return $decision;
            }
        }

        throw new \RuntimeException(
            'Context decision model returned invalid response: '
            . $content
        );
    }
    protected function collectStreamContent(iterable $stream): string
    {
        $buffer = '';

        foreach ($stream as $chunk) {

            if (!is_string($chunk)) {
                continue;
            }

            $buffer .= $chunk;
        }


        $content = '';

        $events = explode(
            "\n\n",
            $buffer
        );


        foreach ($events as $event) {

            if (
                !str_starts_with(
                    trim($event),
                    'data:'
                )
            ) {
                continue;
            }


            $data = trim(
                substr(
                    trim($event),
                    5
                )
            );


            if ($data === '[DONE]') {
                continue;
            }


            $json = json_decode(
                $data,
                true
            );


            if (!is_array($json)) {
                continue;
            }


            /*
             * Cloudflare format
             */
            if (
                isset($json['response'])
                &&
                is_string($json['response'])
            ) {

                $content .= $json['response'];

                continue;
            }


            /*
             * OpenAI compatible
             */
            $delta = data_get(
                $json,
                'choices.0.delta.content'
            );


            if (is_string($delta)) {

                $content .= $delta;

            }
        }


        return trim($content);
    }
    protected function resolveProvider(
        $model
    ): LlmProvider {

        $providerModel = \App\Models\LlmProvider::find(
            $model->provider_id
        );

        if (!$providerModel) {
            throw new \RuntimeException(
                'LLM provider not found.'
            );
        }

        /*
         * Adjust this mapping according to your
         * actual provider classes.
         */
        return $this->{$providerModel->name};
    }
    protected function loadConversation(
        ChatContext $context
    ): void {



        if (!$context->conversationId) {
            $context->setMessages([]);
            $context->setDocuments([]);
            return;
        }

        $messages = $this->conversationLoader->load(
            conversationId: $context->conversationId,
            limit: 10
        );

        if (empty($messages)) {
            $context->setMessages([]);
            $context->setDocuments([]);
            return;
        }

        $documents = [];
        $rawMessages = [];

        foreach ($messages as $message) {
            $content = $this->normalizeMessageContent($message->content);
            $role = $message->role;

            // ۱. فیلتر کردن پاسخ‌های خطای قبلی دستیار که به دلیل عدم شناسایی سند داده بود
            if (
                $role === 'assistant' &&
                (
                    str_contains($content, 'لطفاً سند مورد نظر خود را آپلود کنید') ||
                    str_contains($content, 'برای این درخواست نیاز به پردازش سند دارید')
                )
            ) {
                // این پیام تاریخچه گمراه‌کننده است، از لیست حذف می‌شود
                continue;
            }

            // ۲. استخراج سند و پاکسازی متن قطعی PDF از داخل بدنه پیام کاربر برای جلوگیری از مصرف توکن دوبل
            if (str_contains($content, '[محتوای سند پیوست‌شده]:')) {
                // جدا کردن متن اصلی کاربر از متن قطعه‌قطعه شده‌ی سند
                $parts = explode('[محتوای سند پیوست‌شده]:', $content);
                $cleanUserText = trim($parts[0]);

                // ذخیره سند در اسناد سراسری کانتکست
                $documents[] = [
                    'content' => '[محتوای سند پیوست‌شده]:' . trim($parts[1]),
                    'metadata' => [
                        'title' => 'Uploaded PDF from History'
                    ]
                ];

                // جایگزین کردن محتوای پیام کاربر در تاریخچه با متن تمیز و بدون حجم اضافی PDF
                $content = $cleanUserText !== '' ? $cleanUserText : 'سند پیوست شده';
            }

            $rawMessages[] = new MessageDTO(
                id: (string) ($message->id ?? uniqid()),
                conversation: (string) ($message->conversation_id ?? ''),
                user_id: $message->user_id ?? null,
                role: $role,
                content: $content,
                type: $message->type ?? 'text',
                token_count: (int) ($message->token_count ?? 0),
                metadata: is_array($message->metadata) ? $message->metadata : [],
                created_at: (string) ($message->created_at ?? now())
            );
        }

        $context->setMessages($rawMessages);

        if (!empty($documents)) {
            // حذف مقادیر تکراری احتمالی اسناد
            $context->setDocuments(array_unique($documents, SORT_REGULAR));
        }
    }
    protected function normalizeMessageContent(string $content): string
    {
        $json = json_decode($content, true);

        if (is_array($json) && isset($json['text'])) {
            return $json['text'];
        }

        return $content;
    }
    protected function createRequest(
        ChatContext $context,
        User $user,
        $model
    ): ChatRequest {

        $messages = [];


        /**
         * System + History + User Message
         * ساخته شده توسط PromptPipeline
         */
        foreach ($context->prompt ?? [] as $message) {

            switch ($message['role']) {

                case 'system':

                    $messages[] = ChatMessage::system(
                        $message['content']
                    );

                    break;


                case 'user':

                    $messages[] = ChatMessage::user(
                        $message['content']
                    );

                    break;


                case 'assistant':

                    $messages[] = ChatMessage::assistant(
                        $message['content']
                    );

                    break;

            }

        }


        return new ChatRequest(
            messages: $messages,
            model: $model->key,
            temperature: 0.7,
            maxTokens: 1500
        );
    }



    protected function estimateUsage(
        ChatRequest $request,
        string $response
    ): Usage {
        $prompt = '';


        foreach ($request->messages as $message) {


            if (is_string($message->content)) {

                $prompt .= $message->content . "\n";

            }


            if (is_array($message->content)) {


                foreach ($message->content as $part) {

                    if (isset($part['text'])) {

                        $prompt .= $part['text'] . "\n";

                    }

                }

            }

        }



        $inputTokens = (int) ceil(
            mb_strlen($prompt) / 4
        );


        $outputTokens = (int) ceil(
            mb_strlen($response) / 4
        );


        return new Usage(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            totalTokens: $inputTokens + $outputTokens
        );
    }





    protected function handleIntentResponse(
        ChatContext $context
    ): bool {
        $intent = $context->getMetadata(
            'intent'
        );


        if (!$intent) {

            return false;

        }



        if (
            ($intent['type'] ?? null) === 'title'
        ) {

            $context->setAssistantResponse(
                'برای این درخواست نیاز به پردازش سند دارید.',
                [
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0
                ]
            );


            $context->addMetadata(
                'direct_response',
                true
            );


            return true;

        }


        return false;
    }

    protected function buildImagePrompt(
        ChatContext $context,
        string $currentMessage,
        $model
    ): string {

        $messages = [];

        /*
         * Main model is an image prompt planner.
         */
        $messages[] = ChatMessage::system(
            <<<'PROMPT'
You extract the exact image request and translate it to English.
Do NOT creatively rewrite the request.
Do NOT invent or add details.
Use only information explicitly provided by the current request or relevant history.
If the current request is self-contained, use ONLY the current request and ignore history.
If it references previous content such as "same as before", "previous image", "change it", "change the background", "change its clothes", etc., use the relevant previous image/prompt, preserve unchanged details, and apply ONLY the requested modification.
Previous generated image prompts stored in metadata are the source of truth for previous images.
The final prompt must be self-contained for the image model.
The final output MUST be English.
Return ONLY the final English image-generation prompt.
No explanation. No JSON. No markdown. No quotes. No "Prompt:" prefix.
PROMPT
        );

        /*
         * -----------------------------------------
         * Conversation history
         * -----------------------------------------
         */

        $historyText = '';

        foreach ($context->messages ?? [] as $message) {

            $role = strtoupper(
                (string) $message->role
            );

            $content = trim(
                (string) $message->content
            );

            if ($content === '') {
                continue;
            }

            /*
             * Previous generated image.
             *
             * The actual generated prompt is stored
             * in metadata.
             */
            if (
                $message->role === 'assistant'
                &&
                $message->type === 'image'
            ) {

                $metadata = is_array($message->metadata)
                    ? $message->metadata
                    : [];

                $previousPrompt =
                    $metadata['prompt'] ?? '';

                if ($previousPrompt !== '') {

                    $historyText .=
                        "PREVIOUS GENERATED IMAGE:\n"
                        . $previousPrompt
                        . "\n\n";

                }

                continue;
            }

            $historyText .=
                $role
                . ":\n"
                . $content
                . "\n\n";
        }

        /*
         * Send history only if it exists.
         */
        if ($historyText !== '') {

            $messages[] = ChatMessage::user(
                "CONVERSATION HISTORY:\n\n"
                . $historyText
            );
        }

        /*
         * -----------------------------------------
         * Current request
         * -----------------------------------------
         */

        $messages[] = ChatMessage::user(
            "CURRENT USER REQUEST:\n\n"
            . $currentMessage
        );

        /*
         * -----------------------------------------
         * Main model
         * -----------------------------------------
         */

        $request = new ChatRequest(
            messages: $messages,
            model: $model->key,
            temperature: 0.2,
            maxTokens: 1200
        );

        $provider = $this->resolveProvider(
            $model
        );

        $stream = $provider->stream(
            $request
        );

        return trim(
            $this->collectStreamContent($stream)
        );
    }
}
