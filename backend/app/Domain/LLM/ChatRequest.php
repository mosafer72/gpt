<?php

namespace App\Domain\LLM;

class ChatRequest
{
    /**
     * @param ChatMessage[] $messages
     */
    public function __construct(
        public readonly array $messages,
        public readonly string $model = 'gpt-4.1-mini',
        public readonly float $temperature = 0.7,
        public readonly ?int $maxTokens = null,
    ) {
    }


    public function toArray(): array
    {
        return [
            'model' => $this->model,

            'messages' => array_map(
                fn(ChatMessage $message) => $message->toArray(),
                $this->messages
            ),

            'temperature' => $this->temperature,

            'max_tokens' => $this->maxTokens,
        ];
    }
}
