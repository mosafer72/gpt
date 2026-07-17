<?php

namespace App\Domain\Message\DTOs;

class MessageDTO
{
    public function __construct(

        public readonly string $id,

        public readonly string $conversation,

        public readonly ?string $user_id,

        /**
         * user | assistant | system
         */
        public readonly string $role,

        /**
         * text | image | image_generating
         */
        public readonly string $type = 'text',

        /**
         * متن پیام یا prompt تصویر
         */
        public readonly string $content = '',

        /**
         * آدرس تصویر (در صورت وجود)
         */
        public readonly ?string $image_url = null,

        public readonly int $token_count = 0,

        public readonly ?array $metadata = null,

        public readonly string $created_at = '',

    ) {
    }


    public static function fromArray(array $data): self
    {
        return new self(

            id: (string) ($data['id'] ?? ''),

            conversation: (string) (
                $data['conversation']
                ??
                $data['conversation_id']
                ??
                $data['chat_id']
                ??
                ''
            ),

            user_id: isset($data['user_id'])
                ? (string) $data['user_id']
                : null,

            role: (string) ($data['role'] ?? 'user'),

            type: (string) ($data['type'] ?? 'text'),

            content: (string) ($data['content'] ?? ''),

            image_url: $data['image_url'] ?? null,

            token_count: (int) ($data['token_count'] ?? 0),

            metadata: isset($data['metadata'])
                ? (
                    is_string($data['metadata'])
                    ? json_decode($data['metadata'], true)
                    : $data['metadata']
                )
                : null,

            created_at: (string) ($data['created_at'] ?? now()),
        );
    }


    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'conversation' => $this->conversation,
            'user_id'      => $this->user_id,
            'role'         => $this->role,
            'type'         => $this->type,
            'content'      => $this->content,
            'image_url'    => $this->image_url,
            'token_count'  => $this->token_count,
            'metadata'     => $this->metadata,
            'created_at'   => $this->created_at,
        ];
    }
}
