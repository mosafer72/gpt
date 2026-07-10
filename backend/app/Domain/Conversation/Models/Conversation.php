<?php

namespace App\Domain\Conversation\Models;

use App\Domain\Conversation\DTOs\ConversationDTO;
use App\Domain\Conversation\Schemas\ConversationSchema;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;


#[Table('conversation')]
#[Schema(ConversationSchema::class)]
class Conversation extends Model
{
    private string $id;
    private string $userId;
    private string $title;
    private string $model;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->userId = $data['user_id'] ?? '';
        $this->title = $data['title'] ?? 'New Conversation';
        $this->model = $data['model'] ?? 'gpt-4o';
        $this->status = $data['status'] ?? 'active';
        $this->createdAt = $data['created_at'] ?? '';
        $this->updatedAt = $data['updated_at'] ?? '';
    }

    public static function fromDTO(ConversationDTO $dto): self
    {
        return new self($dto->toArray());
    }

    public function toDTO(): ConversationDTO
    {
        return ConversationDTO::fromArray([
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'model' => $this->model,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ]);
    }

    // متدهای کمکی دامنه
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isDeleted(): bool
    {
        return $this->status === 'deleted';
    }

    public function getShortTitle(int $length = 30): string
    {
        return strlen($this->title) > $length
            ? substr($this->title, 0, $length) . '...'
            : $this->title;
    }

    public function archive(): void
    {
        $this->status = 'archived';
    }

    public function restore(): void
    {
        $this->status = 'active';
    }

    public function softDelete(): void
    {
        $this->status = 'deleted';
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getTitle(): string { return $this->title; }
    public function getModel(): string { return $this->model; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
}
