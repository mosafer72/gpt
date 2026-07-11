<?php

namespace App\Domain\LLM;

class ChatMessage
{
    public function __construct(
        public readonly string $role,
        public readonly mixed $content,
    ) {
    }


    public static function system(string $content): self
    {
        return new self(
            role: 'system',
            content: $content
        );
    }


    public static function user(mixed $content): self
    {
        return new self(
            role: 'user',
            content: $content
        );
    }


    public static function assistant(string $content): self
    {
        return new self(
            role: 'assistant',
            content: $content
        );
    }


    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
