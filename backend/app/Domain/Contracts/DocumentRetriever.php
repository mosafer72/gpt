<?php

namespace App\Domain\Contracts;


interface DocumentRetriever
{


   public function retrieve(

    string $workspaceId,

    string $query,

    int $limit = 5,

    array $documentIds = []

): array;


}
