<?php

namespace App\Domain\Memory\DTOs;

class MemoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $user_id,
        public readonly string $text,
        public readonly int $importance,
        public readonly string $category,
        public readonly ?string $source_message,
        public readonly ?array $embedding,
        public readonly string $last_used,
        public readonly string $created_at,
    ) {
    }

    public static function fromArray(array $data): self
    {
        if (isset($data[0]) && is_array($data[0]) && isset($data[0]['id'])) {
            $data = $data[0];
        }

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
            user_id: $data['user_id'] ?? '',
            text: $data['text'] ?? '',
            importance: (int) ($data['importance'] ?? 5),
            category: $data['category'] ?? 'general',
            source_message: $data['source_message'] ?? null,
            embedding: $data['embedding'] ?? null,
            last_used: $data['last_used'] ?? '',
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
            'user_id' => $this->user_id,
            'text' => $this->text,
            'importance' => $this->importance,
            'category' => $this->category,
            'source_message' => $this->source_message,
            'embedding' => $this->embedding,
            'last_used' => $this->last_used,
            'created_at' => $this->created_at,
        ];
    }

    public function isImportant(): bool
    {
        return $this->importance >= 7;
    }

    public function isCritical(): bool
    {
        return $this->importance >= 9;
    }

    public function getShortText(int $length = 50): string
    {
        return strlen($this->text) > $length
            ? substr($this->text, 0, $length) . '...'
            : $this->text;
    }
}
