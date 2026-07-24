<?php

namespace App\Application\Pipelines;


use App\Application\Context\ChatContext;
use App\Application\Services\TokenWalletService;
use App\Application\Services\UsageService;
use App\Application\Services\WorkspaceService;
use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\DocumentRetriever;
use App\Domain\Contracts\LlmProvider;
use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\MessageStore;
use App\Domain\Contracts\SmallChatProvider;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\Message\DTOs\MessageDTO;
use App\Application\Capabilities\CapabilityResolver;
use App\Jobs\StoreMemoryJob;
use App\Jobs\StoreMessageJob;
use App\Models\AiModel;
use App\Models\Paper;
use App\Models\User;
use App\Http\Services\TokenService;
use App\Application\AI\ImageIntentDetector;
use App\Application\AI\ImageGenerationService;
use App\Application\Services\WorkspaceAccessService;
use App\Application\Services\TokenUsageService;


class ChatPipeline
{


    public function __construct(

        protected ConversationRepository $conversationRepository,

        protected ConversationLoader $conversationLoader,

        protected MemoryPipeline $memoryPipeline,

        protected IntentPipeline $intentPipeline,

        protected PromptPipeline $promptPipeline,

        protected LlmProvider $LlmProvider,

        protected CapabilityResolver $capability,

        protected MessageStore $messageStore,

        protected TokenWalletService $walletService,

        protected SmallChatProvider $smallChat,


        protected WorkspaceService $workspaceService,


        protected ImageIntentDetector $imageIntentDetector,

        protected ImageGenerationService $imageGenerationService,

        protected WorkspaceAccessService $workspaceAccessService,


        protected TokenUsageService $tokenUsageService,

        protected DocumentRetriever $documentRetriever,



    ) {
    }

    public function stream(ChatContext $context)
    {
        $originalUserMessage = $context->currentMessage;


        $user = $this->prepareContext($context);



        $workspace = $this->workspaceAccessService
            ->check(
                $user,
                $context->model,
                1000
            );


        $context->setWorkspace($workspace);



        if (
            $this->capability->enabled(
                $user,
                'planner'
            )
        ) {

            $this->intentPipeline
                ->process(
                    $context,
                    true
                );

        }
        \Log::info('DOCUMENT CAPABILITY', [
            'enabled' => $this->capability->enabled($user, 'documents'),
            'workspace' => $context->workspace->id,
        ]);


        if (
            $this->capability->enabled(
                $user,
                'memory'
            )
        ) {

            StoreMemoryJob::dispatch(
                userId: $context->userId,
                message: $originalUserMessage
            );

        }



        $imageRequest =
            $this->imageIntentDetector
                ->detect(
                    $context->currentMessage
                );


        if ($imageRequest) {

            $result =
                $this->generateImageResponse(
                    $context,
                    $imageRequest
                );


            return collect([

                [

                    'type' => 'image',

                    'image_url' =>
                        $result->metadata['image_url']

                ]

            ]);

        }




        if (
            !empty(
            $context->metadata['rewritten_message']
        )
            &&
            empty(
            $context->metadata['needs_clarification']
        )
        ) {

            $context->currentMessage =
                $context->metadata['rewritten_message'];

        }
        /**
         * Retrieve Documents
         */

        if (
            $this->capability->enabled(
                $user,
                'documents'
            )
        ) {

            $documents =
                $this->documentRetriever
                    ->retrieve(
                        workspaceId: (string) $context->workspace->id,
                        query: $context->currentMessage,
                        limit: 5
                    );

            $context->setDocuments($documents);

        }


        $this->promptPipeline
            ->build($context);





        $model =
            $this->workspaceAccessService
                ->resolveModel(
                    $context->workspace,
                    $context->model
                );




        $request =
            $this->createRequest(
                $context,
                $user,
                $model
            );





        $stream =
            $this->smallChat->stream(

                new ChatRequest(

                    messages:
                    $request->messages,


                    model:
                    $model->key,


                    temperature:
                    $request->temperature,


                    maxTokens:
                    $request->maxTokens

                )

            );






        return (function () use ($stream, $context, $model, $request) {


            $fullResponse = '';



            foreach ($stream as $chunk) {


                if (isset($chunk['content'])) {


                    $content =
                        $chunk['content'];


                    $fullResponse .= $content;



                    yield [

                        'choices' => [

                            [

                                'delta' => [

                                    'content' => $content

                                ]

                            ]

                        ]

                    ];

                }



                if (isset($chunk['error'])) {


                    yield [

                        'error' => $chunk['error']

                    ];

                    return;

                }

            }




            if ($fullResponse !== '') {


                $response =
                    new \App\Domain\LLM\ChatResponse(

                        content: $fullResponse,

                        usage:
                        $this->estimateUsage(
                            $request,
                            $fullResponse
                        )

                    );




                $this->tokenUsageService
                    ->consume(

                        workspace: $context->workspace,

                        model: $model,

                        response: $response,

                        conversationId:
                        $context->conversationId

                    );




                $this->storeAssistantMessage(
                    $context,
                    $model,
                    $response
                );


            }




            yield [

                'done' => true

            ];


        })();

    }

    /**
     * اجرای کامل چرخه Chat
     */
    public function handle(
        ChatContext $context
    ): ChatContext {


        /**
         * Step 1
         *
         * Load Conversation History
         *
         */
        $user = $this->prepareContext($context);

        if (!$user->canSendMessage()) {

            throw new \Exception(
                'تعداد پیام روزانه پلن شما تمام شده است'
            );

        }

        if (
            $this->capability->enabled(
                $user,
                'planner'
            )
        ) {

            $context =
                $this->intentPipeline
                    ->process($context, true);

        }



        /**
         * Step 2
         *
         * Retrieve Memory
         *
         */
        if (
            $this->capability->enabled(
                $user,
                'memory'
            )
            &&
            mb_strlen($context->currentMessage) > 50
        ) {

            $this->loadMemory(
                $context
            );

        }



        /**
         * Step 3
         *
         * Build Prompt
         *
         */

        $imageRequest = $this->imageIntentDetector->detect(
            $context->currentMessage
        );

        if ($imageRequest) {

            $result = $this->generateImageResponse(
                $context,
                $imageRequest
            );


            return collect([
                [
                    'type' => 'image',
                    'image_url' => $result->metadata['image_url']
                ]
            ]);

        }

        if (
            !empty($context->metadata['rewritten_message'])
            &&
            empty($context->metadata['needs_clarification'])
        ) {
            $context->currentMessage =
                $context->metadata['rewritten_message'];
        }
        $this->promptPipeline
            ->build(
                $context
            );

        // =============================
// Image Generation Detection
// =============================






        /**
         * Step 4
         *
         * Send To LLM
         *
         */
        $this->generateResponse(
            $context,
            $user
        );




        $originalUserMessage = $context->currentMessage;



        /**
         * Step 5
         *
         * Store Memory
         *
         */
        if (
            $this->capability->enabled(
                $user,
                'memory'
            )
        ) {

            StoreMemoryJob::dispatch(
                userId: $context->userId,
                message: $originalUserMessage
            );

        }





        return $context;

    }

    protected function loadMemory(
        ChatContext $context
    ): void {


        $memories =
            $this->memoryPipeline
                ->retrieveMemories(
                    userId: $context->userId,
                    query: $context->currentMessage
                );


        $context->setMemories(
            $memories
        );

    }



    protected function generateImageResponse(
        ChatContext $context,
        array $request
    ): ChatContext {


        $imageModel = $this->resolveImageModel();


        $result =
            $this->imageGenerationService->generate(
                prompt: $request['prompt'],
                size: $request['size'],
                model: $imageModel
            );



        $messageDTO = new MessageDTO(

            id: uniqid(),

            conversation:
            (string) $context->conversationId,

            user_id:
            $context->userId,

            role:
            'assistant',

            type:
            'image',

            content:
            'عکس ایجاد شد',

            image_url:
            $result['url'],

            token_count:
            0,

            metadata: [

                'type' => 'image',

                'size' =>
                    $result['size'],

                'model' =>
                    $result['model'],
            ],

            created_at:
            now()->toDateTimeString(),
        );


        $this->messageStore->store(
            $messageDTO
        );


        $context->setAssistantResponse(

            response:
            'عکس ایجاد شد',

            tokenUsage: [

                'prompt_tokens' => 0,

                'completion_tokens' => 0,

                'total_tokens' => 0,
            ]
        );


        $context->metadata = [

            'type' =>
                'image',

            'image_url' =>
                $result['url'],

            'model' =>
                $result['model'],
        ];


        return $context;

    }

    protected function resolveImageModel()
    {
        return AiModel::where(
            'type',
            'image'
        )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();
    }
    /**
     * گرفتن تاریخچه گفتگو
     */
    protected function loadConversation(
        ChatContext $context
    ): void {


        $messages =
            $this->conversationLoader
                ->load(

                    conversationId:
                    $context->conversationId,

                    limit: 8

                );

        $messages = collect($messages)
            ->map(function ($message) {

                return new MessageDTO(

                    id: (string) ($message->id ?? uniqid()),

                    conversation: (string) (
                        $message->chat_id
                        ??
                        $message->conversation_id
                        ??
                        ''
                    ),

                    user_id: isset($message->user_id)
                    ? (string) $message->user_id
                    : null,

                    role: $message->role,

                    content: $message->content,

                    type: $message->type ?? 'text',
                    token_count: (int) ($message->token_count ?? 0),

                    metadata:
                    is_string($message->metadata ?? null)
                    ? json_decode($message->metadata, true)
                    : ($message->metadata ?? null),

                    created_at: (string) (
                        $message->created_at
                        ?? now()
                    )

                );

            })
            ->toArray();


        $context->setMessages(
            $messages
        );

    }









    /**
     * ارتباط با LLM
     */
    protected function generateResponse(
        ChatContext $context,
        User $user
    ): void {


        $model =
            $this->workspaceAccessService
                ->resolveModel(
                    $context->workspace,
                    $context->model
                );


        $request = $this->createRequest(
            $context,
            $user,
            $model
        );



        $response =
            $this->LlmProvider
                ->chat(
                    $request
                );


        $context->setAssistantResponse(
            response: $response->content,
            tokenUsage: [
                'prompt_tokens' => $response->usage->inputTokens,
                'completion_tokens' => $response->usage->outputTokens,
                'total_tokens' => $response->usage->totalTokens,
            ]
        );


        $this->tokenUsageService
            ->consume(

                workspace: $context->workspace,

                model: $model,

                response: $response,

                conversationId: $context->conversationId

            );

    }

    protected function loadConversationEntity(
        ChatContext $context
    ): void {

        $conversation =
            $this->conversationRepository
                ->find(
                    $context->conversationId
                );


        $context->setConversation(
            $conversation
        );

    }

    protected function ensureConversation(
        ChatContext $context
    ): void {


        if ($context->conversationId) {

            return;

        }


        $conversation =
            $this->conversationRepository
                ->create(

                    userId:
                    $context->userId,

                    title:
                    mb_substr(
                        $context->currentMessage,
                        0,
                        100
                    )

                );


        $context->conversationId =
            $conversation->id;


        $context->setConversation(
            $conversation
        );

    }


    protected function prepareContext(
        ChatContext $context
    ): User {

        $this->ensureConversation($context);

        $this->loadConversationEntity($context);

        $this->loadConversation($context);


        $user = User::findOrFail(
            $context->userId
        );


        $workspace =
            $this->workspaceService
                ->current($user);



        if (!$workspace) {

            throw new \Exception(
                'Workspace فعال وجود ندارد'
            );

        }



        $context->setWorkspace(
            $workspace
        );



        return $user;

    }

    protected function createRequest(
        ChatContext $context,
        User $user,
        AiModel $model
    ): ChatRequest {
        return new ChatRequest(

            messages:

            collect($context->prompt)
                ->map(function ($message) {

                    return new ChatMessage(
                        role: $message['role'],
                        content: $message['content']
                    );

                })
                ->values()
                ->all(),

            model:
            $model->key,

            temperature:
            0.7,

            maxTokens:
            $this->capability->value(
                $user,
                'max_tokens',
                700
            )

        );

    }


    protected function estimateUsage(
        ChatRequest $request,
        string $response
    ) {

        $input =
            strlen(
                json_encode($request->messages)
            ) / 4;


        $output =
            strlen($response) / 4;



        return new \App\Domain\LLM\Usage(

            inputTokens: (int) $input,

            outputTokens: (int) $output,

            totalTokens:
            (int) $input + (int) $output

        );

    }

    protected function storeAssistantMessage(
        ChatContext $context,
        AiModel $model,
        $response
    ): void {


        StoreMessageJob::dispatch([


            'id' => uniqid(),


            'conversation' =>
                (string)
                $context->conversationId,


            'user_id' =>
                (string)
                $context->userId,


            'workspace_id' =>
                $context->workspace->id,


            'role' =>
                'assistant',


            'content' =>
                $response->content,


            'token_count' =>
                $response
                    ->usage
                    ->totalTokens,


            'metadata' => [

                'model' =>
                    $model->key,


                'token_usage' => [

                    'prompt_tokens' =>
                        $response
                            ->usage
                            ->inputTokens,


                    'completion_tokens' =>
                        $response
                            ->usage
                            ->outputTokens,


                    'total_tokens' =>
                        $response
                            ->usage
                            ->totalTokens

                ]

            ],


            'created_at' =>
                now()
                    ->toDateTimeString()


        ]);

    }
}
