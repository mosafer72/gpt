<?php

namespace App\Domain\Documents;


class DocumentSection
{

    public function __construct(

        public readonly string $id,

        public readonly string $title,

        public readonly string $content,

        public readonly int $level = 1,

        public readonly int $order = 0,

        public readonly ?int $pageStart = null,

        public readonly ?int $pageEnd = null,

        public readonly array $metadata = [],

    ) {
    }



    public function toArray(): array
    {

        return [

            'id' =>
                $this->id,


            'title' =>
                $this->title,


            'content' =>
                $this->content,


            'level' =>
                $this->level,


            'order' =>
                $this->order,


            'page_start' =>
                $this->pageStart,


            'page_end' =>
                $this->pageEnd,


            'metadata' =>
                $this->metadata,

        ];

    }


}
