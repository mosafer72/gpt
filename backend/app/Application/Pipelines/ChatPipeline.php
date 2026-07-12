<?php

namespace App\Application\Pipelines;


use App\Application\Context\ChatContext;
use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\LLMProvider;
use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\MessageStore;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\Message\DTOs\MessageDTO;



class ChatPipeline
{


    public function __construct(


        protected ConversationRepository $conversationRepository,

        protected ConversationLoader $conversationLoader,

        protected MemoryPipeline $memoryPipeline,

        protected IntentPipeline $intentPipeline,

        protected PromptPipeline $promptPipeline,

        protected LLMProvider $llmProvider,

        protected MessageStore $messageStore

    ) {
    }

    public function stream(ChatContext $context)
    {

        // تاریخچه گفتگو

        $this->loadConversation($context);

        // بازیابی حافظه

        if (mb_strlen($context->currentMessage) > 10) {

            $this->memoryPipeline->retrieve($context);

        }

        // ساخت پرامپت

        $this->promptPipeline->build($context);

        // تبدیل پیام‌ها

        $messages = collect($context->prompt)

            ->map(function ($message) {

                return new ChatMessage(

                    role: $message['role'],

                    content: $message['content']

                );

            })

            ->toArray();

        $request = new ChatRequest(

            messages: $messages,

            model: $context->model,

            temperature: 0.7,

            maxTokens: 2000

        );

        return $this->llmProvider->stream($request);

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
        $context = $this->intentPipeline->process($context);


        logger()->info('AFTER INTENT', [
            'message' => $context->currentMessage,
            'response' => $context->assistantResponse
        ]);


        $this->ensureConversation($context);

        $this->loadConversationEntity($context);


        $this->loadConversation(
            $context
        );





        /**
         * Step 2
         *
         * Retrieve Memory
         *
         */
        if (mb_strlen($context->currentMessage) > 10) {

            $this->memoryPipeline->retrieve($context);

        }



        /**
         * Step 3
         *
         * Build Prompt
         *
         */


        $this->promptPipeline
            ->build(
                $context
            );






        /**
         * Step 4
         *
         * Send To LLM
         *
         */
        $this->generateResponse(
            $context
        );







        /**
         * Step 5
         *
         * Store Memory
         *
         */
        $this->memoryPipeline
            ->store(
                $context
            );





        return $context;

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

                    conversation: (string) ($message->chat_id ?? ''),

                    role: $message->role,

                    content: $message->content,

                    token_count: (int) ($message->token_count ?? 0),

                    metadata: is_string($message->metadata ?? null) ? json_decode($message->metadata, true) : ($message->metadata ?? null),

                    created_at: (string) ($message->created_at ?? now())

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
        ChatContext $context
    ): void {


        $messages = collect($context->prompt)
            ->map(function ($message) {

                return new ChatMessage(
                    role: $message['role'],
                    content: $message['content']
                );

            })
            ->toArray();



        $request = new ChatRequest(

            messages: $messages,

            model: $context->model

        );
        \Log::info('FINAL PROMPT', [
            'messages' => $messages
        ]);


        $response =
            $this->llmProvider
                ->chat(
                    $request
                );



        $context->setAssistantResponse(

            response:
            $response->content,

            tokenUsage:
            $response->usage->totalTokens

        );

        $this->messageStore->store(

            new MessageDTO(

                id: uniqid(),

                conversation:
                $context->conversationId,

                role:
                'assistant',

                content:
                $response->content,

                token_count:
                $response->usage->totalTokens,

                metadata: [
                    'user_id' => $context->userId,
                    'model' => $context->model
                ],

                created_at:
                now()->toDateTimeString()

            )

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
                        50
                    )

                );


        $context->conversationId =
            $conversation->id;


        $context->setConversation(
            $conversation
        );

    }
}
