<?php

namespace App\Domain\Message\Models;

use App\Domain\Message\DTOs\MessageDTO;
use App\Domain\Message\Schemas\MessageSchema;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;

#[Table('Message')]
#[Schema(MessageSchema::class)]
class Message extends Model
{
    private string $id;
    private string $conversationId;
    private string $role;
    private string $content;
    private int $tokenCount;
    private ?array $metadata;
    private string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->conversationId = $data['conversation'] ?? '';
        $this->role = $data['role'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->tokenCount = (int)($data['token_count'] ?? 0);
        $this->metadata = $data['metadata'] ?? null;
        $this->createdAt = $data['created_at'] ?? '';
    }

    public static function fromDTO(MessageDTO $dto): self
    {
        return new self($dto->toArray());
    }

    public function toDTO(): MessageDTO
    {
        return MessageDTO::fromArray([
            'id' => $this->id,
            'conversation' => $this->conversationId,
            'role' => $this->role,
            'content' => $this->content,
            'token_count' => $this->tokenCount,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
        ]);
    }

    // متدهای کمکی دامنه
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    public function isSystem(): bool
    {
        return $this->role === 'system';
    }

    public function getShortContent(int $length = 100): string
    {
        return strlen($this->content) > $length
            ? substr($this->content, 0, $length) . '...'
            : $this->content;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getConversationId(): string { return $this->conversationId; }
    public function getRole(): string { return $this->role; }
    public function getContent(): string { return $this->content; }
    public function getTokenCount(): int { return $this->tokenCount; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): string { return $this->createdAt; }
}
