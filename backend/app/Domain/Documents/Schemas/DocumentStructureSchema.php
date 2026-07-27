<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;


class DocumentStructureSchema implements SchemaContract
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
            DEFINE TABLE IF NOT EXISTS document_structure
            SCHEMAFULL;
            ",



            /*
            |--------------------------------------------------------------------------
            | Core Identity
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS document_id
            ON document_structure
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS workspace_id
            ON document_structure
            TYPE string;
            ",



            /*
            |--------------------------------------------------------------------------
            | Document Information
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS title
            ON document_structure
            TYPE object
            FLEXIBLE;
            ",



            /*
            |--------------------------------------------------------------------------
            | Sections
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS sections
            ON document_structure
            TYPE array;
            ",


            "
            DEFINE FIELD IF NOT EXISTS sections.*
            ON document_structure
            TYPE object
            FLEXIBLE;
            ",



            /*
            |--------------------------------------------------------------------------
            | Statistics
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS statistics
            ON document_structure
            TYPE object
            FLEXIBLE;
            ",



            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS metadata
            ON document_structure
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
            ON document_structure
            TYPE datetime
            DEFAULT time::now();
            ",


            "
            DEFINE FIELD IF NOT EXISTS updated_at
            ON document_structure
            TYPE datetime
            DEFAULT time::now();
            ",



            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            "
            DEFINE INDEX IF NOT EXISTS idx_structure_document
            ON document_structure
            COLUMNS document_id
            UNIQUE;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_structure_workspace
            ON document_structure
            COLUMNS workspace_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_structure_workspace_document
            ON document_structure
            COLUMNS workspace_id, document_id;
            ",

        ];

    }



    public function rules(): array
    {
        return [];
    }

}
