<?php

namespace App\Application\Pipelines;

use App\Application\AI\ImageGenerationService;
use App\Application\AI\ImageIntentDetector;
use App\Application\Capabilities\CapabilityResolver;
use App\Application\Context\ChatContext;
use App\Application\Services\TokenUsageService;
use App\Application\Services\WorkspaceAccessService;
use App\Application\Services\WorkspaceService;

use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\DocumentRetriever;
use App\Domain\Contracts\LlmProvider;
use App\Domain\Contracts\MessageStore;
use App\Domain\Contracts\SmallChatProvider;

use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;

use App\Domain\LLM\Usage;
use App\Domain\Message\DTOs\MessageDTO;

use App\Jobs\StoreMemoryJob;
use App\Jobs\StoreMessageJob;

use App\Models\AiModel;
use App\Models\User;


class ChatPipeline
{

    public function __construct(

        protected ConversationRepository $conversationRepository,

        protected ConversationLoader $conversationLoader,

        protected MemoryPipeline $memoryPipeline,

        protected IntentPipeline $intentPipeline,

        protected PromptPipeline $promptPipeline,

        protected LlmProvider $llmProvider,

        protected SmallChatProvider $smallChatProvider,

        protected CapabilityResolver $capabilityResolver,

        protected MessageStore $messageStore,

        protected WorkspaceAccessService $workspaceAccessService,

        protected TokenUsageService $tokenUsageService,

        protected DocumentRetriever $documentRetriever,

        protected ImageIntentDetector $imageIntentDetector,

        protected ImageGenerationService $imageGenerationService,

        protected WorkspaceService $workspaceService,

    ) {
    }



    protected bool $useSmallModel = true;



    public function handle(
        ChatContext $context
    ): ChatContext {


        $user =
            $this->prepareContext(
                $context
            );


        $this->checkWorkspaceAccess(
            $context,
            $user
        );


        $this->runPreProcessing(
            $context,
            $user
        );



        if (
            $this->isImageRequest($context)
        ) {

            return $this->generateImageResponse(
                $context
            );

        }



        $this->retrieveContext(
            $context,
            $user
        );



        $this->promptPipeline
            ->build(
                $context
            );



        $response =
            $this->generateResponse(
                $context,
                $user
            );



        $this->afterResponse(
            $context,
            $user,
            $response
        );


        return $context;

    }




    public function stream(
        ChatContext $context
    ): iterable {


        $user =
            $this->prepareContext(
                $context
            );


        $this->checkWorkspaceAccess(
            $context,
            $user
        );


        $this->runPreProcessing(
            $context,
            $user
        );



        if (
            $this->isImageRequest($context)
        ) {


            return collect([

                [

                    'type' => 'image',

                    'image_url' =>
                        $this->generateImageResponse($context)
                            ->metadata['image_url']

                ]

            ]);

        }



        $this->retrieveContext(
            $context,
            $user
        );


        $this->promptPipeline
            ->build(
                $context
            );



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
            $this->useSmallModel

            ?

            $this->smallChatProvider
                ->stream(
                    $request
                )

            :

            $this->llmProvider
                ->stream(
                    $request
                );



        return $this->streamResponse(
            $stream,
            $context,
            $model,
            $request
        );

    }




    protected function checkWorkspaceAccess(
        ChatContext $context,
        User $user
    ): void {


        $this->workspaceAccessService
            ->check(
                user:
                $user,

                modelKey:
                $context->model
            );

    }




    protected function runPreProcessing(
        ChatContext $context,
        User $user
    ): void {


        $capabilities =
            $this->capabilityResolver
                ->all($user);



        $context->addMetadata(
            'capabilities',
            $capabilities
        );



        if (
            $capabilities['planner'] ?? false
        ) {


            $this->intentPipeline
                ->process(
                    $context,
                    true
                );

        }



        if (

            ($capabilities['memory'] ?? false)

            &&

            mb_strlen(
                trim(
                    $context->currentMessage
                )
            ) > 50

        ) {


            $this->loadMemory(
                $context
            );

        }



        if (
            !empty(
            $context->metadata['rewritten_message']
        )
        ) {


            $context->currentMessage =

                $context->metadata['rewritten_message'];

        }


    }



    protected function retrieveContext(
        ChatContext $context,
        User $user
    ): void {


        if (
            !$this->shouldUseDocuments(
                $context,
                $user
            )
        ) {

            return;

        }



        $result =
            $this->documentRetriever
                ->retrieve(

                    workspaceId:
                    (string)
                    $context->workspace->id,

                    query:
                    $context->currentMessage,

                    limit:
                    5
                );



        $context->setDocuments(
            $result['chunks'] ?? []
        );



        $context->addMetadata(
            'document_context',
            $result['text'] ?? ''
        );


    }
    protected function shouldUseDocuments(
        ChatContext $context,
        User $user
    ): bool {


        if (
            !$this->capabilityResolver
                ->enabled(
                    $user,
                    'documents'
                )
        ) {

            return false;

        }



        return (bool)

            $context->getMetadata(
                'use_documents',
                true
            );


    }





    protected function loadMemory(
        ChatContext $context
    ): void {


        $memories =
            $this->memoryPipeline
                ->retrieveMemories(

                    userId:
                    $context->userId,

                    query:
                    $context->currentMessage

                );



        $context->setMemories(
            $memories
        );


    }





    protected function isImageRequest(
        ChatContext $context
    ): bool {


        return (bool)

            $this->imageIntentDetector
                ->detect(
                    $context->currentMessage
                );

    }





    protected function generateImageResponse(
        ChatContext $context
    ): ChatContext {


        $intent =
            $this->imageIntentDetector
                ->detect(
                    $context->currentMessage
                );



        $model =
            AiModel::where(
                'type',
                'image'
            )
                ->where(
                    'is_active',
                    true
                )
                ->firstOrFail();



        $result =
            $this->imageGenerationService
                ->generate(

                    prompt:
                    $intent['prompt'],

                    size:
                    $intent['size'],

                    model:
                    $model

                );



        $context->setAssistantResponse(
            'تصویر ایجاد شد',
            [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0
            ]
        );



        $context->addMetadata(
            'type',
            'image'
        );



        $context->addMetadata(
            'image_url',
            $result['url']
        );



        $context->addMetadata(
            'model',
            $model->key
        );



        $this->messageStore
            ->store(

                new MessageDTO(

                    id:
                    uniqid(),

                    conversation:
                    (string)
                    $context->conversationId,

                    user_id:
                    $context->userId,

                    role:
                    'assistant',

                    type:
                    'image',

                    content:
                    'تصویر ایجاد شد',

                    image_url:
                    $result['url'],

                    token_count:
                    0,

                    metadata:
                    [
                        'model' =>
                            $model->key
                    ],

                    created_at:
                    now()
                        ->toDateTimeString()

                )

            );



        return $context;

    }





    protected function generateResponse(
        ChatContext $context,
        User $user
    ): ChatResponse {


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



        $response =
            $this->useSmallModel

            ?

            $this->smallChatProvider
                ->chat(
                    $request
                )

            :

            $this->llmProvider
                ->chat(
                    $request
                );



        $context->setAssistantResponse(

            $response->content,

            [

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

        );



        $this->tokenUsageService
            ->consume(

                workspace:
                $context->workspace,

                model:
                $model,

                response:
                $response,

                conversationId:
                $context->conversationId

            );



        return $response;

    }





    protected function afterResponse(
        ChatContext $context,
        User $user,
        ChatResponse $response
    ): void {



        StoreMessageJob::dispatch([

            'id' =>
                uniqid(),


            'conversation' =>
                (string)
                $context->conversationId,


            'user_id' =>
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


            'metadata' =>
                [
                    'model' =>
                        $context->model
                ],


            'created_at' =>
                now()
                    ->toDateTimeString()

        ]);




        if (

            $this->capabilityResolver
                ->enabled(
                    $user,
                    'memory'
                )

        ) {


            StoreMemoryJob::dispatch(

                userId:
                $context->userId,


                message:
                $context->currentMessage

            );

        }


    }
    protected function streamResponse(
        iterable $stream,
        ChatContext $context,
        AiModel $model,
        ChatRequest $request
    ): iterable {


        return (function () use ($stream, $context, $model, $request) {


            $full = '';



            foreach ($stream as $chunk) {



                if (
                    isset($chunk['content'])
                ) {


                    $content =
                        $chunk['content'];



                    $full .= $content;



                    yield [

                        'choices' => [

                            [

                                'delta' => [

                                    'content' =>
                                        $content

                                ]

                            ]

                        ]

                    ];

                }





                if (
                    isset($chunk['error'])
                ) {


                    yield [

                        'error' =>
                            $chunk['error']

                    ];


                    return;

                }


            }





            if ($full !== '') {


                $response =
                    new ChatResponse(

                        content:
                        $full,


                        usage:
                        $this->estimateUsage(

                            $request,

                            $full

                        )

                    );




                $this->tokenUsageService
                    ->consume(

                        workspace:
                        $context->workspace,


                        model:
                        $model,


                        response:
                        $response,


                        conversationId:
                        $context->conversationId

                    );





                $this->afterResponse(

                    $context,


                    User::findOrFail(
                        $context->userId
                    ),


                    $response

                );


            }




            yield [

                'done' => true

            ];




        })();


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



        $user =
            User::findOrFail(
                $context->userId
            );



        $workspace =
            $this->workspaceService
                ->current(
                    $user
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


        if (
            $context->conversationId
        ) {

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





    protected function loadConversation(
        ChatContext $context
    ): void {



        $messages =
            $this->conversationLoader
                ->load(

                    conversationId:
                    $context->conversationId,


                    limit:
                    10

                );





        $context->setMessages(

            collect($messages)

                ->map(

                    function ($message) {


                        return new MessageDTO(

                            id:
                            (string)
                            (
                                $message->id
                                ??
                                uniqid()
                            ),


                            conversation:
                            (string)
                            (
                                $message->conversation_id
                                ??
                                ''
                            ),


                            user_id:
                            $message->user_id
                            ??
                            null,


                            role:
                            $message->role,


                            content:
                            $message->content,


                            type:
                            $message->type
                            ??
                            'text',


                            token_count:
                            (int)
                            (
                                $message->token_count
                                ??
                                0
                            ),


                            metadata:
                            is_array(
                                $message->metadata
                            )
                            ?
                            $message->metadata
                            :
                            [],


                            created_at:
                            (string)
                            (
                                $message->created_at
                                ??
                                now()
                            )

                        );


                    }

                )

                ->toArray()

        );


    }

    protected function createRequest(
        ChatContext $context,
        User $user,
        $model
    ): ChatRequest {


        $messages = [];



        foreach ($context->prompt as $message) {


            $messages[] = new ChatMessage(

                role: $message['role'],

                content: $message['content']

            );

        }



        $subscription =
            $context->workspace
                ->currentSubscription;



        $plan =
            $subscription
                    ?->workspacePlan;



        return new ChatRequest(

            messages: $messages,

            model: $model->model_name,

            temperature: 0.7,

            maxTokens:

            data_get(

                $plan?->capabilities,

                'max_tokens',

                4000

            )

        );


    }

    protected function estimateUsage(
        ChatRequest $request,
        string $response
    ): Usage {

        $prompt = '';

        foreach ($request->messages as $message) {
            $prompt .= $message->content . "\n";
        }

        // تخمین تقریبی: هر ۴ کاراکتر ≈ ۱ توکن
        $inputTokens = (int) ceil(mb_strlen($prompt) / 4);
        $outputTokens = (int) ceil(mb_strlen($response) / 4);

        return new Usage(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            totalTokens: $inputTokens + $outputTokens,
        );
    }
}
