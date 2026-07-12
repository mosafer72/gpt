<?php

namespace App\Application\Context;

use App\Domain\Conversation\DTOs\ConversationDTO;
use App\Domain\Message\DTOs\MessageDTO;
use App\Domain\Memory\DTOs\MemoryDTO;

class ChatContext
{
    /**
     * شناسه کاربر
     */
    public string $userId;

    /**
     * شناسه مکالمه
     */
    public ?string $conversationId;
    /**
     * پیامی که کاربر همین الان ارسال کرده
     */
    public string $currentMessage;

    /**
     * مدل انتخابی
     */
    public string $model;

    /**
     * اطلاعات مکالمه
     */
    public ?ConversationDTO $conversation = null;

    /**
     * پیام‌های قبلی مکالمه
     *
     * @var MessageDTO[]
     */
    public array $messages = [];

    /**
     * حافظه‌های بازیابی شده
     *
     * @var MemoryDTO[]
     */
    public array $memories = [];

    /**
     * خروجی PromptPipeline
     *
     * فعلاً array است
     * بعداً می‌تواند Prompt Object شود.
     */
    public array $prompt = [];

    /**
     * پاسخ مدل
     */
    public ?string $assistantResponse = null;

    /**
     * تعداد توکن مصرف شده
     */
    public array $tokenUsage = [

        'prompt_tokens' => 0,

        'completion_tokens' => 0,

        'total_tokens' => 0,

    ];

    /**
     * اطلاعات اضافی
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

    /**
     * Conversation
     */
    public function setConversation(?ConversationDTO $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    /**
     * Messages
     *
     * @param MessageDTO[] $messages
     */
    /**
     * Messages
     *
     * @param MessageDTO[] $messages
     */
    public function setMessages(array $messages): self
    {
        foreach ($messages as $message) {

            if (!$message instanceof MessageDTO) {
                throw new \InvalidArgumentException(
                    'ChatContext messages must be MessageDTO instances.'
                );
            }
        }

        $this->messages = $messages;

        return $this;
    }

    public function addMessage(MessageDTO $message): self
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Memories
     *
     * @param MemoryDTO[] $memories
     */
    public function setMemories(array $memories): self
    {
        $this->memories = $memories;

        return $this;
    }

    public function addMemory(MemoryDTO $memory): self
    {
        $this->memories[] = $memory;

        return $this;
    }

    /**
     * Prompt
     */
    public function setPrompt(array $prompt): self
    {
        $this->prompt = $prompt;

        return $this;
    }

    /**
     * LLM Response
     */
    public function setAssistantResponse(
        string $response,
        int $tokenUsage = 0
    ): self {
        $this->assistantResponse = $response;
        $this->tokenUsage = $tokenUsage;

        return $this;
    }

    /**
     * Metadata
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * تبدیل به آرایه
     */
    public function toArray(): array
    {


        return [
            'user_id' => $this->userId,
            'conversation_id' => $this->conversationId,
            'current_message' => $this->currentMessage,
            'model' => $this->model,
            'conversation' => $this->conversation?->toArray(),
            'messages' => array_map(
                fn(MessageDTO $m) => $m->toArray(),
                $this->messages
            ),
            'memories' => array_map(
                fn(MemoryDTO $m) => $m->toArray(),
                $this->memories
            ),
            'prompt' => $this->prompt,
            'assistant_response' => $this->assistantResponse,
            'token_usage' => $this->tokenUsage,
            'metadata' => $this->metadata,
        ];
    }

}
