<?php

namespace App\Domain\Documents\Contracts;


interface DocumentVectorRepositoryInterface
{


    public function store(
        array $data
    );



    public function storeBulk(
        array $vectors
    ): void;



    public function search(

        string $workspaceId,

        array $vector,

        int $limit = 10,

        array $documentIds = [],

        array $filters = []

    ): array;



    public function deleteDocument(
        string $documentId
    );



    public function debugWorkspace(
        string $workspaceId
    );


}
