<?php

namespace App\Application\Context;

use App\Domain\Conversation\DTOs\ConversationDTO;
use App\Domain\Message\DTOs\MessageDTO;
use App\Domain\Memory\DTOs\MemoryDTO;
use App\Models\Workspace;



class ChatContext
{


    /**
     * User
     */
    public string $userId;



    /**
     * Conversation
     */
    public ?string $conversationId;



    /**
     * Current user message
     */
    public string $currentMessage;



    /**
     * Selected Model
     */
    public string $model;



    /**
     * Workspace
     */
    public ?Workspace $workspace = null;



    /**
     * Conversation DTO
     */
    public ?ConversationDTO $conversation = null;



    /**
     * Chat History
     *
     * @var MessageDTO[]
     */
    public array $messages = [];



    /**
     * Memories
     *
     * @var MemoryDTO[]
     */
    public array $memories = [];



    /**
     * Retrieved Documents
     *
     * نتیجه Search در SurrealDB
     *
     */
    public array $documents = [];



    /**
     * Prompt
     */
    public array $prompt = [];



    /**
     * Assistant Response
     */
    public ?string $assistantResponse = null;



    /**
     * Token Usage
     */
    public array $tokenUsage = [

        'prompt_tokens' => 0,

        'completion_tokens' => 0,

        'total_tokens' => 0

    ];



    /**
     * Metadata
     */
    public array $metadata = [];




    public function __construct(
        string $userId,
        ?string $conversationId,
        string $currentMessage,
        string $model = 'gpt-4o'
    ) {

        $this->userId = $userId;

        $this->conversationId = $conversationId;

        $this->currentMessage = $currentMessage;

        $this->model = $model;

    }





    public function setWorkspace(
        ?Workspace $workspace
    ): self {

        $this->workspace = $workspace;

        return $this;

    }






    public function setConversation(
        ?ConversationDTO $conversation
    ): self {

        $this->conversation = $conversation;

        return $this;

    }







    public function setMessages(
        array $messages
    ): self {


        foreach ($messages as $message) {


            if (!$message instanceof MessageDTO) {

                throw new \InvalidArgumentException(
                    'Messages must be MessageDTO'
                );

            }

        }


        $this->messages = $messages;


        return $this;

    }






    public function addMessage(
        MessageDTO $message
    ): self {

        $this->messages[] = $message;


        return $this;

    }








    public function setMemories(
        array $memories
    ): self {

        $this->memories = $memories;


        return $this;

    }






    public function addMemory(
        MemoryDTO $memory
    ): self {

        $this->memories[] = $memory;


        return $this;

    }






    /**
     * Document Context
     */
    public function setDocuments(
        array $documents
    ): self {

        $this->documents = $documents;


        return $this;

    }
    public function setSelectedDocuments(
        array $ids
    ): self {

        $this->metadata['document_ids'] = $ids;

        return $this;

    }

    public function addDocument(
        array $document
    ): self {

        $this->documents[] = $document;


        return $this;

    }







    public function setPrompt(
        array $prompt
    ): self {

        $this->prompt = $prompt;


        return $this;

    }








    public function setAssistantResponse(
        string $response,
        array $tokenUsage = []
    ): self {

        $this->assistantResponse = $response;


        $this->tokenUsage = $tokenUsage;


        return $this;

    }






    public function setMetadata(
        array $metadata
    ): self {

        $this->metadata = $metadata;


        return $this;

    }






    public function addMetadata(
        string $key,
        mixed $value
    ): self {

        $this->metadata[$key] = $value;


        return $this;

    }








    public function toArray(): array
    {

        return [

            'user_id' => $this->userId,

            'conversation_id' => $this->conversationId,

            'current_message' => $this->currentMessage,

            'model' => $this->model,

            'workspace' =>
                $this->workspace?->toArray(),


            'conversation' =>
                $this->conversation?->toArray(),


            'messages' => array_map(

                fn(MessageDTO $m) =>
                $m->toArray(),

                $this->messages

            ),



            'memories' => array_map(

                fn(MemoryDTO $m) =>
                $m->toArray(),

                $this->memories

            ),



            'documents' => $this->documents,



            'prompt' => $this->prompt,


            'assistant_response' =>
                $this->assistantResponse,


            'token_usage' =>
                $this->tokenUsage,


            'metadata' =>
                $this->metadata

        ];

    }







    public static function fromArray(
        array $data
    ): self {


        $context = new self(

            userId:
            $data['user_id'],

            conversationId:
            $data['conversation_id'] ?? null,

            currentMessage:
            $data['current_message'] ?? '',

            model:
            $data['model'] ?? 'gpt-4o'

        );



        if (isset($data['workspace'])) {


            $context->setWorkspace(

                Workspace::find(
                    $data['workspace']['id']
                )

            );

        }




        $context->messages =
            $data['messages'] ?? [];



        $context->memories =
            $data['memories'] ?? [];



        $context->documents =
            $data['documents'] ?? [];



        $context->prompt =
            $data['prompt'] ?? [];



        $context->assistantResponse =
            $data['assistant_response'] ?? null;



        $context->tokenUsage =
            $data['token_usage']
            ??
            [

                'prompt_tokens' => 0,

                'completion_tokens' => 0,

                'total_tokens' => 0

            ];



        $context->metadata =
            $data['metadata'] ?? [];



        return $context;


    }



}
