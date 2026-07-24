<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;


class DocumentSectionPlanRepository
{


    protected string $table =
        'document_section_plan';




    public function store(
        array $data
    ) {


        /*
        |--------------------------------------------------------------------------
        | Normalize DB Fields
        |--------------------------------------------------------------------------
        */


        $data['document_id'] =
            (string)
            ($data['document_id'] ?? '');



        $data['workspace_id'] =
            (string)
            ($data['workspace_id'] ?? '');



        $data['section'] =
            (string)
            ($data['section'] ?? '');




        $data['section_title'] =
            $this->normalizeString(
                $data['section_title'] ?? ''
            );



        $data['summary'] =
            $this->normalizeString(
                $data['summary'] ?? ''
            );



        $data['keywords'] =
            $this->normalizeStringArray(
                $data['keywords'] ?? []
            );



        $data['question_types'] =
            $this->normalizeStringArray(
                $data['question_types'] ?? []
            );


        foreach ($data as $key => $value) {

            if (
                in_array(
                    $key,
                    [
                        'section_title',
                        'summary',
                        'section'
                    ],
                    true
                )
            ) {

                $data[$key] =
                    (string) $value;

            }

        }

        return Surreal::query(

            new BoundQuery(

                query:

                "
                CREATE {$this->table}
                CONTENT \$payload;
                ",

                bindings: [

                    'payload' => $data

                ]

            )

        );


    }







    public function storeBulk(
        array $sections
    ): void {


        foreach ($sections as $section) {


            $this->store(
                $section
            );


        }


    }








    public function findTopSections(

        string $workspaceId,

        string $documentId,

        int $limit = 3

    ) {


        $sql = "

        SELECT *

        FROM {$this->table}

        WHERE

            workspace_id = \$workspace

        AND

            document_id = \$document


        ORDER BY

            answer_probability DESC


        LIMIT \$limit;

        ";




        return Surreal::query(

            new BoundQuery(

                query: $sql,

                bindings: [

                    'workspace' =>
                        $workspaceId,


                    'document' =>
                        $documentId,


                    'limit' =>
                        $limit

                ]

            )

        );


    }







    public function searchRelevantSections(

        string $workspaceId,

        string $documentId,

        string $question,

        int $limit = 3

    ): array {



        $sql = "

        SELECT *

        FROM {$this->table}

        WHERE

            workspace_id = \$workspace

        AND

            document_id = \$document


        ORDER BY

            answer_probability DESC


        LIMIT \$limit;

        ";




        $response =
            Surreal::query(

                new BoundQuery(

                    query: $sql,

                    bindings: [

                        'workspace' =>
                            $workspaceId,


                        'document' =>
                            $documentId,


                        'limit' =>
                            $limit

                    ]

                )

            );




        return $this->formatResults(
            $response
        );


    }








    protected function normalizeString(
        mixed $value
    ): string {


        if ($value === null) {

            return '';

        }



        if (is_string($value)) {

            return trim($value);

        }



        if (is_array($value)) {

            return trim(
                json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE
                )
            );

        }



        return (string) $value;

    }







    protected function normalizeStringArray(
        mixed $value
    ): array {


        if (!is_array($value)) {

            return [];

        }



        return collect($value)

            ->map(
                fn($item) =>
                (string) $item
            )

            ->values()

            ->toArray();


    }







    protected function formatResults(
        $response
    ): array {


        if (empty($response)) {

            return [];

        }



        if (
            is_object($response)
            &&
            method_exists(
                $response,
                'getData'
            )
        ) {

            $response =
                $response->getData();

        }



        if (!is_array($response)) {

            return [];

        }



        if (
            isset($response[0])
            &&
            isset($response[0]['result'])
        ) {

            return $response[0]['result'];

        }



        return $response;


    }


}
