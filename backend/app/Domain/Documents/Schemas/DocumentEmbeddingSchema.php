<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;


class DocumentEmbeddingSchema implements SchemaContract
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
            DEFINE TABLE IF NOT EXISTS document_embedding
            SCHEMAFULL;
            ",




            /*
            |--------------------------------------------------------------------------
            | Core Fields
            |--------------------------------------------------------------------------
            */


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



            /*
            |--------------------------------------------------------------------------
            | Vector
            |--------------------------------------------------------------------------
            */


            "
            DEFINE FIELD IF NOT EXISTS embedding
            ON document_embedding
            TYPE array;
            ",





            /*
            |--------------------------------------------------------------------------
            | Academic Metadata
            |--------------------------------------------------------------------------
            */


            "
            DEFINE FIELD IF NOT EXISTS metadata
            ON document_embedding
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







            /*
            |--------------------------------------------------------------------------
            | Normal Indexes
            |--------------------------------------------------------------------------
            */


            "
            DEFINE INDEX IF NOT EXISTS
            idx_embedding_workspace

            ON document_embedding

            COLUMNS workspace_id;
            ",



            "
            DEFINE INDEX IF NOT EXISTS
            idx_embedding_document

            ON document_embedding

            COLUMNS document_id;
            ",



            "
            DEFINE INDEX IF NOT EXISTS
            idx_embedding_chunk

            ON document_embedding

            COLUMNS chunk_id;
            ",





            /*
            |--------------------------------------------------------------------------
            | Duplicate Protection
            |--------------------------------------------------------------------------
            */


            "
            DEFINE INDEX IF NOT EXISTS
            idx_document_chunk_unique

            ON document_embedding

            COLUMNS document_id, chunk_id
            UNIQUE;
            ",



        ];

    }





    public function rules(): array
    {

        return [];

    }


}
