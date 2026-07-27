<?php

namespace App\Application\DTOs\Documents;


class RetrievalResult
{


    public function __construct(

        public string $content,

        public float $score,

        public array $metadata = []

    )
    {

    }




    public function section(): ?string
    {

        return

            $this->metadata['section']
            ??
            null;

    }





    public function title(): ?string
    {

        return

            $this->metadata['title']
            ??
            null;

    }




}
