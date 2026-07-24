<?php

namespace App\Domain\Documents\Models;


use App\Domain\Documents\Schemas\DocumentVectorSchema;

use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;



#[Table('document_vector')]
#[Schema(DocumentVectorSchema::class)]
class DocumentVector extends Model
{

    private string $id;

    private string $chunkId;

    private string $documentId;

    private string $workspaceId;

    private string $content;

    private array $embedding;

    private array $metadata;

    private string $createdAt;

    private string $updatedAt;



    public function __construct(array $data)
    {

        $this->id =
            $data['id'] ?? '';



        $this->chunkId =
            $data['chunk_id'] ?? '';



        $this->documentId =
            $data['document_id'] ?? '';



        $this->workspaceId =
            $data['workspace_id'] ?? '';



        $this->content =
            $data['content'] ?? '';



        $this->embedding =
            $data['embedding'] ?? [];



        $this->metadata =
            $data['metadata'] ?? [];



        $this->createdAt =
            $data['created_at'] ?? '';



        $this->updatedAt =
            $data['updated_at'] ?? '';

    }





    public function getId(): string
    {
        return $this->id;
    }



    public function getChunkId(): string
    {
        return $this->chunkId;
    }



    public function getDocumentId(): string
    {
        return $this->documentId;
    }



    public function getWorkspaceId(): string
    {
        return $this->workspaceId;
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



    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }



    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }


}
