<?php

namespace App\Domain\Contracts;


interface EmbeddingProvider
{

    public function embed(
        string $text
    ): array;



    public function embedMany(
        array $texts
    ): array;

}
