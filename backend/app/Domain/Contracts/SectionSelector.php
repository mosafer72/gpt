<?php

namespace App\Domain\Contracts;


interface SectionSelector
{


    public function select(

        string $workspaceId,

        string $documentId,

        string $question,

        int $limit = 3

    ): array;


}
