<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;
use Illuminate\Support\Facades\Log;



class DocumentSectionPlanRepository
{


    protected string $table =
        'document_section_plan';







    public function store(
        array $data
    ) {


        $payload = $this->normalizePayload(
            $data
        );



        return Surreal::query(

            new BoundQuery(

                query: "

                CREATE {$this->table}

                CONTENT \$payload;

                ",

                bindings: [

                    'payload' =>
                        $payload

                ]

            )

        );


    }








    public function storeBulk(
        array $sections
    ): void {


        foreach ($sections as $section) {


            try {


                $this->store(
                    $section
                );


            } catch (\Throwable $e) {


                Log::warning(

                    'SECTION PLAN STORE FAILED',

                    [

                        'error' =>
                            $e->getMessage(),

                        'section' =>
                            $section['section'] ?? null

                    ]

                );


            }


        }


    }








    public function findTopSections(

        string $workspaceId,

        string $documentId,

        int $limit = 20

    ): array {



        $response =
            Surreal::query(

                new BoundQuery(

                    query: "

                    SELECT *

                    FROM {$this->table}

                    WHERE

                        workspace_id = \$workspace

                    AND

                        document_id = \$document


                    ORDER BY

                        answer_probability DESC


                    LIMIT \$limit;


                    ",

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










    public function searchRelevantSections(

        string $workspaceId,

        string $documentId,

        string $question,

        int $limit = 5

    ): array {


        Log::debug(

            'SECTION PLAN SEARCH',

            [

                'question' => $question

            ]

        );



        return $this->findTopSections(

            workspaceId:
            $workspaceId,

            documentId:
            $documentId,

            limit:
            $limit

        );


    }









    protected function normalizePayload(

        array $data

    ): array {


        $probability =
            (float)
            (
                $data['answer_probability']
                ??
                0
            );



        if ($probability > 1) {

            $probability =
                $probability / 100;

        }




        return [



            'document_id' =>

                (string)
                (
                    $data['document_id']
                    ??
                    ''
                ),



            'workspace_id' =>

                (string)
                (
                    $data['workspace_id']
                    ??
                    ''
                ),




            'section' =>

                strtolower(
                    trim(
                        (string)
                        (
                            $data['section']
                            ??
                            ''
                        )
                    )
                ),




            'section_title' =>

                $this->normalizeString(
                    $data['section_title'] ?? ''
                ),




            'summary' =>

                $this->normalizeString(
                    $data['summary'] ?? ''
                ),




            'keywords' =>

                $this->normalizeStringArray(
                    $data['keywords'] ?? []
                ),




            'question_types' =>

                $this->normalizeStringArray(
                    $data['question_types'] ?? []
                ),





            'answer_probability' =>

                min(
                    max(
                        $probability,
                        0
                    ),
                    1
                ),




            'planner_rank' =>

                (int)
                (
                    $data['planner_rank']
                    ??
                    0
                ),




            'selected_count' =>

                (int)
                (
                    $data['selected_count']
                    ??
                    0
                ),




            'metadata' =>

                $data['metadata']
                ??
                [],




            'created_at' =>

                now()->toISOString()

        ];



    }









    protected function normalizeString(
        mixed $value
    ): string {


        if ($value === null) {

            return '';

        }


        if (
            is_array($value)
            ||
            is_object($value)
        ) {

            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
            );

        }


        return trim(
            (string) $value
        );


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
            isset($response[0]['result'])
        ) {

            return $response[0]['result'];

        }



        return is_array($response)

            ?

            $response

            :

            [];


    }




}
