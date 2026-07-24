<?php

namespace App\Domain\Documents\Schemas;


use Surqlize\Model\SchemaContract;



class DocumentStructureSchema implements SchemaContract
{


    public function definitions(): array
    {

        return [


            "DEFINE TABLE IF NOT EXISTS document_structure SCHEMAFULL;",



            "DEFINE FIELD IF NOT EXISTS document_id
            ON document_structure
            TYPE string;",



            "DEFINE FIELD IF NOT EXISTS workspace_id
            ON document_structure
            TYPE string;",




            "DEFINE FIELD IF NOT EXISTS title
            ON document_structure
            TYPE object
            FLEXIBLE;",




            "DEFINE FIELD IF NOT EXISTS sections
            ON document_structure
            TYPE array;",





            "DEFINE FIELD IF NOT EXISTS created_at
            ON document_structure
            TYPE datetime
            DEFAULT time::now();",






            "DEFINE INDEX IF NOT EXISTS
            idx_structure_document

            ON document_structure

            COLUMNS document_id;"

        ];

    }






    public function rules(): array
    {

        return [];

    }


}
