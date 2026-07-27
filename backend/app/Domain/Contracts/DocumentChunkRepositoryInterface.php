<?php

namespace App\Domain\Documents\Contracts;


interface DocumentChunkRepositoryInterface
{


    public function store(
        array $data
    ): array;



    public function findByDocument(
        string $documentId
    ): array;



    public function findByWorkspace(
        string $workspaceId
    ): array;



    public function find(
        string $id
    ): array;



    public function deleteDocument(
        string $documentId
    ): mixed;



    public function countByDocument(
        string $documentId
    ): int;



    public function searchContent(
        string $workspaceId,
        string $keyword,
        int $limit = 10
    ): array;


}
