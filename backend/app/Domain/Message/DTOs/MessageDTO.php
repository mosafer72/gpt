<?php

namespace App\Domain\Message\DTOs;

class MessageDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $conversation,
        public readonly string $role,
        public readonly string $content,
        public readonly int $token_count,
        public readonly ?array $metadata,
        public readonly string $created_at,
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
            conversation: $data['conversation'] ?? '',
            role: $data['role'] ?? '',
            content: $data['content'] ?? '',
            token_count: (int) ($data['token_count'] ?? 0),
            metadata: $data['metadata'] ?? null,
            created_at: $data['created_at'] ?? '',
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
            'conversation' => $this->conversation,
            'role' => $this->role,
            'content' => $this->content,
            'token_count' => $this->token_count,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }

    // ... بقیه متدها
}
