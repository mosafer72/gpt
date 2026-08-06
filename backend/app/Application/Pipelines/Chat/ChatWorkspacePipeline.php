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

class ChatWorkspacePipeline implements WorkspacePipeline
{
    protected bool $useSmallModel = true;

    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected ConversationLoader $conversationLoader,
        protected MemoryPipeline $memoryPipeline,
        protected IntentPipeline $intentPipeline,
        protected PromptPipeline $promptPipeline,
        protected CloudflareProvider $llmProvider,
        protected CapabilityResolver $capabilityResolver,
        protected MessageStore $messageStore,
        protected WorkspaceAccessService $workspaceAccessService,
        protected TokenUsageService $tokenUsageService,
        protected TokenWalletService $tokenWalletService,
        protected ImageIntentDetector $imageIntentDetector,
        protected ImageGenerationService $imageGenerationService,
        protected WorkspaceService $workspaceService
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

    public function stream(ChatContext $context, callable $send): void
    {
        if (session()->isStarted()) {
            session()->save();
        }


        $user = $this->prepareContext($context);


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


        $this->promptPipeline->build($context);


        $model = $this->workspaceAccessService->resolveModel(
            $context->workspace,
            $context->model
        );




        $request = $this->createRequest(
            $context,
            $user,
            $model
        );

        \Log::info('FINAL PROMPT', [
            'messages' => $request
        ]);
        $stream = $this->llmProvider->stream($request);



        $fullContent = '';

        $promptTokens = 0;

        $completionTokens = 0;



        /**
         * Intent detection
         */
        $buffer = '';

        $toolBuffer = '';

        $intentChecked = false;

        $imageMode = false;

        $chunkCount = 0;



        foreach ($stream as $chunk) {



            if (is_object($chunk) && isset($chunk->usage)) {

                $promptTokens =
                    $chunk->usage->promptTokens ?? $promptTokens;


                $completionTokens =
                    $chunk->usage->completionTokens ?? $completionTokens;
            }



            $content = null;


            if (is_object($chunk)) {

                $content =
                    $chunk->choices[0]->delta->content ?? null;

            } else {

                $content =
                    $chunk['choices'][0]['delta']['content'] ?? null;
            }



            if (!$content) {
                continue;
            }


            /**
             * ===============================
             * FIRST CHUNKS DETECTION
             * ===============================
             */
            if (!$intentChecked) {


                $chunkCount++;


                $buffer .= $content;


                $clean = ltrim($buffer);



                /**
                 * Ignore empty first chunks
                 */
                if (
                    $clean === ''
                    ||
                    $clean === "\n\n"
                ) {

                    if ($chunkCount <= 5) {
                        continue;
                    }

                }



                /**
                 * Detect tool call
                 */
                if (
                    str_contains(
                        $clean,
                        '<tool_call>'
                    )
                    ||
                    str_contains(
                        $clean,
                        'generate_image'
                    )
                ) {


                    $imageMode = true;

                    $intentChecked = true;


                    /**
                     * do not send tool output
                     */
                    $toolBuffer .= $buffer;


                    continue;
                }



                /**
                 * Detect old format
                 */
                if (
                    trim($clean) === '1'
                ) {


                    $imageMode = true;

                    $intentChecked = true;


                    continue;

                }



                /**
                 * After 5 chunks decide normal chat
                 */
                if ($chunkCount >= 5) {


                    $intentChecked = true;


                    $fullContent .= $buffer;


                    $send([
                        'choices' => [
                            [
                                'delta' => [
                                    'content' => $buffer
                                ]
                            ]
                        ]
                    ]);


                    $buffer = '';

                }


                continue;

            }




            /**
             * ===============================
             * IMAGE TOOL MODE
             * ===============================
             */
            if ($imageMode) {


                $toolBuffer .= $content;



                if (
                    !str_contains(
                        $toolBuffer,
                        '</tool_call>'
                    )
                ) {

                    continue;

                }



                preg_match(
                    '/\{.*\}/s',
                    $toolBuffer,
                    $matches
                );



                if (
                    !empty($matches[0])
                ) {


                    $toolData = json_decode(
                        $matches[0],
                        true
                    );



                    if (
                        isset($toolData['prompt'])
                    ) {


                        /**
                         * frontend loader state
                         */
                        $send([
                            'status' => 'generating_image'
                        ]);



                        $this->streamImageResponse(
                            $context,
                            $send,
                            [
                                'prompt' => $toolData['prompt'],
                                'make_pic' => true
                            ]
                        );


                        return;

                    }

                }



                return;

            }




            /**
             * ===============================
             * NORMAL STREAM
             * ===============================
             */

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



    protected function isImageRequest(
        ChatContext $context
    ): bool {
        return (bool) $this->imageIntentDetector->detect(
            $context->currentMessage
        );
    }



    protected function streamImageResponse(
        ChatContext $context,
        callable $send,
        array $intent
    ): void {

        // notify frontend
        $send([
            'status' => 'generating_image'
        ]);


        // $model = AiModel::where(
        //     'type',
        //     'image'
        // )
        //     ->where(
        //         'is_active',
        //         true
        //     )
        //     ->firstOrFail();

        $model = AiModel::
            where(
                'is_active',
                true
            )
            ->firstOrFail();


        $result = $this->imageGenerationService->generate(
            prompt: $intent['prompt'],
            size: $intent['size'] ?? '1024x1024',
            model: $model
        );


        $send([
            'choices' => [
                [
                    'delta' => [
                        'content' => 'تصویر ایجاد شد'
                    ],
                    'image' => $result['url']
                ]
            ]
        ]);


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
                    'model' => $model->key,
                    'prompt' => $intent['prompt']
                ],
                created_at: now()->toDateTimeString()
            )
        );


        $send([
            'done' => true
        ]);
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

        $response = $provider->chat(
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


        $this->loadConversation(
            $context
        );


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
            maxTokens: 4000
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
}
