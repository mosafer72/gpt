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

    private string $id;

    private string $documentId;

    private int $chunkIndex;

    private string $content;

    private int $tokens;

    private string $createdAt;



    public function __construct(array $data)
    {

        $this->id =
            $data['id'] ?? '';

        $this->documentId =
            $data['document_id'] ?? '';

        $this->chunkIndex =
            $data['chunk_index'] ?? 0;

        $this->content =
            $data['content'] ?? '';

        $this->tokens =
            $data['tokens'] ?? 0;

        $this->createdAt =
            $data['created_at'] ?? '';

    }





    public function getId(): string
    {
        return $this->id;
    }



    public function getDocumentId(): string
    {
        return $this->documentId;
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



    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }


}
