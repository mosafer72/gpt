<?php

namespace App\Domain\Documents\Contracts;


interface DocumentSectionTranslationRepositoryInterface
{


    public function store(
        array $data
    ): array;



    public function findByDocument(
        string $documentId
    ): array;



    public function findSection(
        string $documentId,
        string $section
    ): array;



    public function deleteDocument(
        string $documentId
    ): mixed;


}
