<?php

namespace App\Domain\Memory\Models;

use App\Domain\Memory\DTOs\MemoryDTO;
use App\Domain\Memory\Schemas\MemorySchema;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;

#[Table('Memory')]
#[Schema(MemorySchema::class)]
class Memory extends Model
{
    private string $id;
    private string $userId;
    private string $text;
    private int $importance;
    private string $category;
    private ?string $sourceMessage;
    private ?array $embedding;
    private string $lastUsed;
    private string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->userId = $data['user_id'] ?? '';
        $this->text = $data['text'] ?? '';
        $this->importance = (int)($data['importance'] ?? 5);
        $this->category = $data['category'] ?? 'general';
        $this->sourceMessage = $data['source_message'] ?? null;
        $this->embedding = $data['embedding'] ?? null;
        $this->lastUsed = $data['last_used'] ?? '';
        $this->createdAt = $data['created_at'] ?? '';
    }

    public static function fromDTO(MemoryDTO $dto): self
    {
        return new self($dto->toArray());
    }

    public function toDTO(): MemoryDTO
    {
        return MemoryDTO::fromArray([
            'id' => $this->id,
            'user_id' => $this->userId,
            'text' => $this->text,
            'importance' => $this->importance,
            'category' => $this->category,
            'source_message' => $this->sourceMessage,
            'embedding' => $this->embedding,
            'last_used' => $this->lastUsed,
            'created_at' => $this->createdAt,
        ]);
    }

    // متدهای کمکی دامنه
    public function isImportant(): bool
    {
        return $this->importance >= 7;
    }

    public function isCritical(): bool
    {
        return $this->importance >= 9;
    }

    public function increaseImportance(int $amount = 1): void
    {
        $this->importance = min(10, $this->importance + $amount);
    }

    public function decreaseImportance(int $amount = 1): void
    {
        $this->importance = max(0, $this->importance - $amount);
    }

    public function touch(): void
    {
        $this->lastUsed = now()->toISOString();
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getText(): string { return $this->text; }
    public function getImportance(): int { return $this->importance; }
    public function getCategory(): string { return $this->category; }
    public function getSourceMessage(): ?string { return $this->sourceMessage; }
    public function getEmbedding(): ?array { return $this->embedding; }
    public function getLastUsed(): string { return $this->lastUsed; }
    public function getCreatedAt(): string { return $this->createdAt; }
}
