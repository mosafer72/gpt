<?php

namespace App\Domain\LLM;

class ChatResponse
{
    public function __construct(

        public readonly string $content,

        public readonly Usage $usage,

        public readonly ?string $model = null,

        public readonly array $raw = []

    ) {
    }



    public static function fromOpenAI(array $response): self
    {
        return new self(

            content:
            $response['choices'][0]['message']['content']
            ?? '',


            usage:
            Usage::fromArray(
                $response['usage'] ?? []
            ),


            model:
            $response['model'] ?? null,


            raw:
            $response
        );
    }



    public function toArray(): array
    {
        return [

            'content' => $this->content,

            'model' => $this->model,

            'usage' => $this->usage?->toArray(),

        ];
    }
}
