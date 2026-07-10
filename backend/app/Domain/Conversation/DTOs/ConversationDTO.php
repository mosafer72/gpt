<?php

namespace App\Domain\Conversation\DTOs;

class ConversationDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $title,
        public readonly string $model,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        // اگر داده به صورت [[...]] است، اولین عنصر را بگیر
        if (isset($data[0]) && is_array($data[0]) && isset($data[0]['id'])) {
            $data = $data[0];
        }

        // اگر داده درون کلید 'result' یا 'data' است
        if (isset($data['result']) && is_array($data['result'])) {
            $data = $data['result'];
            if (isset($data[0]) && is_array($data[0])) {
                $data = $data[0];
            }
        }
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
            if (isset($data[0]) && is_array($data[0])) {
                $data = $data[0];
            }
        }

        return new self(
            id: $data['id'] ?? '',
            userId: $data['user_id'] ?? '',
            title: $data['title'] ?? 'New Conversation',
            model: $data['model'] ?? 'gpt-4o',
            status: $data['status'] ?? 'active',
            createdAt: $data['created_at'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
        );
    }

    public static function collectionFromArray(array $data): array
    {
        $items = [];
        $flatten = function ($array) use (&$flatten, &$items) {
            foreach ($array as $value) {
                if (is_array($value)) {
                    if (isset($value['id'])) {
                        $items[] = $value;
                    } else {
                        $flatten($value);
                    }
                }
            }
        };
        $flatten($data);
        return array_map(fn($item) => self::fromArray($item), $items);
    }
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'model' => $this->model,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

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
}
