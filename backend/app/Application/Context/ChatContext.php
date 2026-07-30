<?php

namespace App\Application\Context;

use App\Domain\Conversation\DTOs\ConversationDTO;
use App\Domain\Message\DTOs\MessageDTO;
use App\Domain\Memory\DTOs\MemoryDTO;
use App\Models\Workspace;

class ChatContext
{
    public string $userId;
    public ?string $conversationId;
    public string $currentMessage;
    public string $model;

    public ?Workspace $workspace = null;
    public ?ConversationDTO $conversation = null;

    /**
     * @var MessageDTO[]
     */
    public array $messages = [];

    /**
     * @var MemoryDTO[]
     */
    public array $memories = [];

    /**
     * Retrieved RAG chunks
     */
    public array $documents = [];

    /**
     * Final LLM prompt messages
     */
    public array $prompt = [];

    public ?string $assistantResponse = null;

    public array $tokenUsage = [
        'prompt_tokens' => 0,
        'completion_tokens' => 0,
        'total_tokens' => 0
    ];

    /**
     * Pipeline shared state
     */
    public array $metadata = [];

    public function __construct(
        string $userId,
        ?string $conversationId,
        string $currentMessage,
        string $model = 'qwen2.5'
    ) {
        $this->userId = $userId;
        $this->conversationId = $conversationId;
        $this->currentMessage = $currentMessage;
        $this->model = $model;
    }

    public function setWorkspace(?Workspace $workspace): self
    {
        $this->workspace = $workspace;
        return $this;
    }

    public function setConversation(?ConversationDTO $conversation): self
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function setMessages(array $messages): self
    {
        foreach ($messages as $message) {
            if (!$message instanceof MessageDTO) {
                throw new \InvalidArgumentException('Messages must be MessageDTO');
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

    public function setDocuments(array $documents): self
    {
        $this->documents = $documents;
        return $this;
    }

    public function addDocument(array $document): self
    {
        $this->documents[] = $document;
        return $this;
    }

    /**
     * مقداردهی یکپارچه شناسه‌های اسناد منتخب (سینگل یا چندتایی)
     */
    public function setSelectedDocuments(array|string|null $ids): self
    {
        if (empty($ids)) {
            $this->metadata['document_ids'] = [];
            return $this;
        }

        if (is_string($ids)) {
            $ids = [$ids];
        }

        $this->metadata['document_ids'] = array_values(array_unique(array_filter($ids)));
        return $this;
    }

    /**
     * دریافت امن لیست شناسه‌های اسناد
     */
    public function getDocumentIds(): array
    {
        $ids = $this->metadata['document_ids'] ?? [];

        if (is_string($ids) && !empty($ids)) {
            return [$ids];
        }

        return is_array($ids) ? $ids : [];
    }

    /**
     * دریافت بهترین کئوری برای جستجو (اگر بازنویسی شده باشد متن بازنویسی‌شده را می‌دهد)
     */
    public function getSearchQuery(): string
    {
        return $this->getMetadata('rewritten_message') ?? $this->currentMessage;
    }

    public function setPrompt(array $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function setAssistantResponse(string $response, array $tokenUsage = []): self
    {
        $this->assistantResponse = $response;

        if (!empty($tokenUsage)) {
            $this->tokenUsage = array_merge($this->tokenUsage, $tokenUsage);
        }

        return $this;
    }

    public function setTokenUsage(array $usage): self
    {
        $this->tokenUsage = array_merge($this->tokenUsage, $usage);
        return $this;
    }

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

    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'conversation_id' => $this->conversationId,
            'current_message' => $this->currentMessage,
            'model' => $this->model,
            'workspace' => $this->workspace?->toArray(),
            'conversation' => $this->conversation?->toArray(),
            'messages' => array_map(fn(MessageDTO $m) => $m->toArray(), $this->messages),
            'memories' => array_map(fn(MemoryDTO $m) => $m->toArray(), $this->memories),
            'documents' => $this->documents,
            'prompt' => $this->prompt,
            'assistant_response' => $this->assistantResponse,
            'token_usage' => $this->tokenUsage,
            'metadata' => $this->metadata
        ];
    }

    /**
     * ساخت هوشمند Context از روی داده‌های Request یا Array
     */
    public static function fromArray(array $data): self
    {
        $context = new self(
            userId: $data['user_id'] ?? '',
            conversationId: $data['conversation_id'] ?? null,
            currentMessage: $data['current_message'] ?? $data['message'] ?? '',
            model: $data['model'] ?? 'qwen2.5'
        );

        if (!empty($data['workspace']['id'])) {
            $context->setWorkspace(Workspace::find($data['workspace']['id']));
        }

        $context->messages = $data['messages'] ?? [];
        $context->memories = $data['memories'] ?? [];
        $context->documents = $data['documents'] ?? [];
        $context->prompt = $data['prompt'] ?? [];
        $context->assistantResponse = $data['assistant_response'] ?? null;
        $context->tokenUsage = $data['token_usage'] ?? [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0
        ];

        // ست کردن متادیتا
        $context->metadata = $data['metadata'] ?? [];

        // ✅ استخراج استخراج هوشمند ID اسناد از ورودی اصلی یا متادیتا
        $documentIds = $data['document_ids']
            ?? $data['document_id']
            ?? $data['metadata']['document_ids']
            ?? $data['metadata']['document_id']
            ?? null;

        if ($documentIds) {
            $context->setSelectedDocuments($documentIds);
        }

        return $context;
    }
}
