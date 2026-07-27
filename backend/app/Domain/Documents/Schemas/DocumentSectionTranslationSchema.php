<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;


class DocumentSectionTranslationSchema implements SchemaContract
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
            DEFINE TABLE IF NOT EXISTS document_section_translation
            SCHEMAFULL;
            ",



            /*
            |--------------------------------------------------------------------------
            | Core Identity
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS document_id
            ON document_section_translation
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS workspace_id
            ON document_section_translation
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS section
            ON document_section_translation
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS title
            ON document_section_translation
            TYPE string;
            ",



            /*
            |--------------------------------------------------------------------------
            | Translation Content
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS original_content
            ON document_section_translation
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS translated_content
            ON document_section_translation
            TYPE string;
            ",


            "
            DEFINE FIELD IF NOT EXISTS summary_fa
            ON document_section_translation
            TYPE string;
            ",


            /*
            |--------------------------------------------------------------------------
            | Search Data
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS keywords
            ON document_section_translation
            TYPE array
            DEFAULT [];
            ",


            "
            DEFINE FIELD IF NOT EXISTS embedding
            ON document_section_translation
            TYPE array
            DEFAULT [];
            ",


            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            "
            DEFINE FIELD IF NOT EXISTS metadata
            ON document_section_translation
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
            ON document_section_translation
            TYPE datetime
            DEFAULT time::now();
            ",


            "
            DEFINE FIELD IF NOT EXISTS updated_at
            ON document_section_translation
            TYPE datetime
            DEFAULT time::now();
            ",



            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            "
            DEFINE INDEX IF NOT EXISTS idx_translation_document
            ON document_section_translation
            COLUMNS document_id;
            ",


            "
            DEFINE INDEX IF NOT EXISTS idx_translation_workspace
            ON document_section_translation
            COLUMNS workspace_id;
            ",


            /*
            |--------------------------------------------------------------------------
            | Duplicate Protection
            |--------------------------------------------------------------------------
            */

            "
            DEFINE INDEX IF NOT EXISTS idx_translation_unique
            ON document_section_translation
            COLUMNS document_id, section
            UNIQUE;
            ",

        ];

    }



    public function rules(): array
    {
        return [];
    }

}
