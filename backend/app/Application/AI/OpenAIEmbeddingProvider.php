<?php

namespace App\Application\AI;

use App\Domain\Contracts\EmbeddingProvider;

class OpenAIEmbeddingProvider implements EmbeddingProvider
{
    public function embed(
        string $text
    ): array {

        return [
            0.12,
            0.44,
            0.91
        ];

    }

    public function embedMany(
        array $texts
    ): array {

        return collect($texts)
            ->map(
                fn ($text) => $this->embed($text)
            )
            ->toArray();

    }
}
