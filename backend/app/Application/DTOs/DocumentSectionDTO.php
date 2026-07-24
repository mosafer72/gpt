<?php

namespace App\Application\DTOs;


class DocumentSectionDTO
{


    public function __construct(

        public string $section,

        public string $title,

        public string $content,

        public int $chunkCount = 0

    )
    {

    }



}
