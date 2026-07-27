<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;


class DocumentChunkSchema implements SchemaContract
{

    public function definitions(): array
    {

        return [

            /*
            |--------------------------------------------------------------------------
            | Table
            |--------------------------------------------------------------------------
            */

            "
            DEFINE TABLE IF NOT EXISTS document_chunk
            SCHEMAFULL;
            ",



            /*
            |--------------------------------------------------------------------------
            | Core Fields
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS document_id
            ON document_chunk
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS workspace_id
            ON document_chunk
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS chunk_index
            ON document_chunk
            TYPE int;
            ",


            "
            DEFINE FIELD IF NOT EXISTS content
            ON document_chunk
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS tokens
            ON document_chunk
            TYPE int
            DEFAULT 0;
            ",


            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS metadata
            ON document_chunk
            TYPE object
            FLEXIBLE;
            ",


            /*
            |--------------------------------------------------------------------------
            | Dates
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS created_at
            ON document_chunk
            TYPE datetime
            DEFAULT time::now();
            ",


            "
            DEFINE FIELD IF NOT EXISTS updated_at
            ON document_chunk
            TYPE datetime
            DEFAULT time::now();
            ",



            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            "
            DEFINE INDEX IF NOT EXISTS idx_chunk_document
            ON document_chunk
            COLUMNS document_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_chunk_workspace
            ON document_chunk
            COLUMNS workspace_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_chunk_document_order
            ON document_chunk
            COLUMNS document_id, chunk_index;
            ",



            /*
            |--------------------------------------------------------------------------
            | Duplicate Protection
            |--------------------------------------------------------------------------
            */

            "
            DEFINE INDEX IF NOT EXISTS idx_chunk_unique
            ON document_chunk
            COLUMNS document_id, chunk_index
            UNIQUE;
            ",


        ];

    }



    public function rules(): array
    {
        return [];
    }

}
