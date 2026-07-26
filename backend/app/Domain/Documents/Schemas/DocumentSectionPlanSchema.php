<?php

namespace App\Domain\Documents\Schemas;


use Surqlize\Model\SchemaContract;



class DocumentSectionPlanSchema implements SchemaContract
{


    public function definitions(): array
    {


        return [



            "
            DEFINE TABLE IF NOT EXISTS document_section_plan
            SCHEMAFULL;
            ",





            /*
            |--------------------------------------------------------------------------
            | Core
            |--------------------------------------------------------------------------
            */


            "
            DEFINE FIELD IF NOT EXISTS document_id
            ON document_section_plan
            TYPE string;
            ",



            "
            DEFINE FIELD IF NOT EXISTS workspace_id
            ON document_section_plan
            TYPE string;
            ",



            "
            DEFINE FIELD IF NOT EXISTS section
            ON document_section_plan
            TYPE string;
            ",



            "
            DEFINE FIELD IF NOT EXISTS section_title
            ON document_section_plan
            TYPE string;
            ",





            /*
            |--------------------------------------------------------------------------
            | AI Planner Data
            |--------------------------------------------------------------------------
            */


            "
            DEFINE FIELD IF NOT EXISTS summary
            ON document_section_plan
            TYPE string;
            ",



            "
            DEFINE FIELD IF NOT EXISTS keywords
            ON document_section_plan
            TYPE array;
            ",



            "
            DEFINE FIELD IF NOT EXISTS question_types
            ON document_section_plan
            TYPE array;
            ",



            "
            DEFINE FIELD IF NOT EXISTS answer_probability
            ON document_section_plan
            TYPE float
            DEFAULT 0;
            ",



            "
            DEFINE FIELD IF NOT EXISTS planner_rank
            ON document_section_plan
            TYPE int
            DEFAULT 0;
            ",



            "
            DEFINE FIELD IF NOT EXISTS selected_count
            ON document_section_plan
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
            ON document_section_plan
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
            ON document_section_plan
            TYPE datetime
            DEFAULT time::now();
            ",



            "
            DEFINE FIELD IF NOT EXISTS updated_at
            ON document_section_plan
            TYPE datetime
            DEFAULT time::now();
            ",







            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */


            "
            DEFINE INDEX IF NOT EXISTS
            idx_section_plan_document_workspace

            ON document_section_plan

            COLUMNS document_id, workspace_id;
            ",



            "
            DEFINE INDEX IF NOT EXISTS
            idx_section_plan_probability

            ON document_section_plan

            COLUMNS answer_probability;
            ",



            "
            DEFINE INDEX IF NOT EXISTS
            idx_section_plan_rank

            ON document_section_plan

            COLUMNS planner_rank;
            ",



            "
            DEFINE INDEX IF NOT EXISTS
            idx_section_plan_unique_section

            ON document_section_plan

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
