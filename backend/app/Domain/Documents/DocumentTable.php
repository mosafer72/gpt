<?php

namespace App\Domain\Documents;


class DocumentTable
{

    public function __construct(

        public readonly string $id,

        public readonly ?string $caption = null,

        public readonly ?int $page = null,

        public readonly array $metadata = [],

    ) {
    }



    public function toArray(): array
    {

        return [

            'id'=>$this->id,

            'caption'=>$this->caption,

            'page'=>$this->page,

            'metadata'=>$this->metadata,

        ];

    }

}
