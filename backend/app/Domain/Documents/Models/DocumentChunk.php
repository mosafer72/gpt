<?php

namespace App\Domain\Documents\Models;


use App\Domain\Documents\Schemas\DocumentChunkSchema;

use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;



#[Table('document_chunk')]
#[Schema(DocumentChunkSchema::class)]
class DocumentChunk extends Model
{


    private string $id = '';

    private string $documentId = '';

    private string $workspaceId = '';

    private int $chunkIndex = 0;

    private string $content = '';

    private int $tokens = 0;

    private array $metadata = [];

    private ?string $createdAt = null;






    public function __construct(
        array $data = []
    )
    {


        $this->id =
            (string)
            (
                $data['id']
                ??
                ''
            );




        $this->documentId =
            (string)
            (
                $data['document_id']
                ??
                ''
            );




        $this->workspaceId =
            (string)
            (
                $data['workspace_id']
                ??
                ''
            );




        $this->chunkIndex =
            (int)
            (
                $data['chunk_index']
                ??
                0
            );




        $this->content =
            (string)
            (
                $data['content']
                ??
                ''
            );




        $this->tokens =
            (int)
            (
                $data['tokens']
                ??
                0
            );




        $this->metadata =
            is_array(
                $data['metadata']
                ??
                null
            )

            ?

            $data['metadata']

            :

            [];





        $this->createdAt =
            $data['created_at']
            ??
            null;



    }









    public function getId(): string
    {
        return $this->id;
    }







    public function getDocumentId(): string
    {
        return $this->documentId;
    }







    public function getWorkspaceId(): string
    {
        return $this->workspaceId;
    }







    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }







    public function getContent(): string
    {
        return $this->content;
    }







    public function getTokens(): int
    {
        return $this->tokens;
    }







    public function getMetadata(): array
    {
        return $this->metadata;
    }







    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }








    /*
    |--------------------------------------------------------------------------
    | Retrieval Helpers
    |--------------------------------------------------------------------------
    */


    public function getSection(): ?string
    {

        return

            $this->metadata['section']
            ??
            null;

    }






    public function getImportance(): ?string
    {

        return

            $this->metadata['importance']
            ??
            null;

    }






    public function isAcademic(): bool
    {

        return

            (
                $this->metadata['type']
                ??
                null
            )

            ===

            'academic_paper';

    }



}
