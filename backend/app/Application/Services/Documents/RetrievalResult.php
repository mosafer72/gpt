<?php

namespace App\Application\DTOs\Documents;



class RetrievalResult
{


    public function __construct(


        public readonly string $chunkId,


        public readonly string $content,


        public readonly float $score,


        public readonly array $metadata = []


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








    public function importance(): ?string
    {

        return

            $this->metadata['importance']
            ??
            null;

    }








    public function isHighImportance(): bool
    {

        return in_array(

            $this->importance(),

            [

                'high',

                'very_high'

            ],

            true

        );

    }








    public function toArray(): array
    {

        return [

            'chunk_id'=>

                $this->chunkId,


            'content'=>

                $this->content,


            'score'=>

                $this->score,


            'metadata'=>

                $this->metadata

        ];

    }


}
