<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;


class DocumentSectionTranslationSchema implements SchemaContract
{

    public function definitions(): array
    {

        return [

            "
            DEFINE TABLE IF NOT EXISTS document_section_translation
            SCHEMAFULL;
            ",

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


            "
            DEFINE FIELD IF NOT EXISTS keywords
            ON document_section_translation
            TYPE array;
            ",


            "
            DEFINE FIELD IF NOT EXISTS embedding
            ON document_section_translation
            TYPE array;
            ",


            "
            DEFINE FIELD IF NOT EXISTS metadata
            ON document_section_translation
            TYPE object FLEXIBLE;
            ",


            "
            DEFINE FIELD IF NOT EXISTS created_at
            ON document_section_translation
            TYPE datetime
            DEFAULT time::now();
            ",


            "
            DEFINE INDEX IF NOT EXISTS
            idx_translation_document

            ON document_section_translation

            COLUMNS document_id;
            "


        ];

    }



    public function rules(): array
    {
        return [];
    }

}
