<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;


class DocumentEmbeddingSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [

            "
            DEFINE TABLE IF NOT EXISTS document_embedding
            SCHEMAFULL;
            ",


            "
            DEFINE FIELD IF NOT EXISTS document_id
            ON document_embedding
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS workspace_id
            ON document_embedding
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS chunk_id
            ON document_embedding
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS content
            ON document_embedding
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS embedding
            ON document_embedding
            TYPE array;
            ",


            "
            DEFINE FIELD IF NOT EXISTS metadata
            ON document_embedding
            TYPE object
            FLEXIBLE;
            ",


            "
            DEFINE FIELD IF NOT EXISTS created_at
            ON document_embedding
            TYPE datetime
            DEFAULT time::now();
            ",


            "
            DEFINE FIELD IF NOT EXISTS updated_at
            ON document_embedding
            TYPE datetime
            DEFAULT time::now();
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_embedding_workspace
            ON document_embedding
            COLUMNS workspace_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_embedding_document
            ON document_embedding
            COLUMNS document_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_embedding_chunk
            ON document_embedding
            COLUMNS chunk_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_embedding_workspace_document
            ON document_embedding
            COLUMNS workspace_id, document_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_embedding_document_chunk_unique
            ON document_embedding
            COLUMNS document_id, chunk_id
            UNIQUE;
            ",


            /*
            |--------------------------------------------------------------------------
            | Vector Index
            |--------------------------------------------------------------------------
            */

            "
            DEFINE INDEX IF NOT EXISTS idx_embedding_vector
            ON document_embedding
            FIELDS embedding
            HNSW DIMENSION 1024 DIST COSINE;
            ",

        ];
    }


    public function rules(): array
    {
        return [];
    }
}
