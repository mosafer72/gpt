<?php

namespace App\Domain\Documents\Models;


use App\Domain\Documents\Schemas\DocumentEmbeddingSchema;

use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;



#[Table('document_embedding')]
#[Schema(DocumentEmbeddingSchema::class)]
class DocumentEmbedding extends Model
{


    private string $id = '';

    private string $documentId = '';

    private string $workspaceId = '';

    private string $chunkId = '';

    private string $content = '';

    private array $embedding = [];

    private array $metadata = [];

    private ?string $createdAt = null;

    private ?string $updatedAt = null;







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




        $this->chunkId =
            (string)
            (
                $data['chunk_id']
                ??
                ''
            );




        $this->content =
            (string)
            (
                $data['content']
                ??
                ''
            );






        $this->embedding =

            is_array(
                $data['embedding']
                ??
                null
            )

            ?

            array_map(
                'floatval',
                $data['embedding']
            )

            :

            [];







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




        $this->updatedAt =
            $data['updated_at']
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






    public function getChunkId(): string
    {
        return $this->chunkId;
    }






    public function getContent(): string
    {
        return $this->content;
    }






    public function getEmbedding(): array
    {
        return $this->embedding;
    }






    public function getMetadata(): array
    {
        return $this->metadata;
    }






    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }






    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }








    /*
    |--------------------------------------------------------------------------
    | Academic Metadata Helpers
    |--------------------------------------------------------------------------
    */





    public function getSection(): ?string
    {

        return

            $this->metadata['section']
            ??
            null;

    }








    public function getSectionTitle(): ?string
    {

        return

            $this->metadata['section_title']
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








    public function getChunkType(): ?string
    {

        return

            $this->metadata['chunk_type']
            ??
            null;

    }








    public function isAcademicPaper(): bool
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
