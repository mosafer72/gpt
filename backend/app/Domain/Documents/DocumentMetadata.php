<?php

namespace App\Domain\Documents;


class DocumentMetadata
{

    public function __construct(

        public readonly ?string $title = null,

        public readonly array $authors = [],

        public readonly ?string $year = null,

        public readonly ?string $doi = null,

        public readonly array $keywords = [],

        public readonly ?string $abstract = null,

    ) {
    }



    public function toArray(): array
    {
        return [

            'title' =>
                $this->title,

            'authors' =>
                $this->authors,

            'year' =>
                $this->year,

            'doi' =>
                $this->doi,

            'keywords' =>
                $this->keywords,

            'abstract' =>
                $this->abstract,

        ];
    }

}
