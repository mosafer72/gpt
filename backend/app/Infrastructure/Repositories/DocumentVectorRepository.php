<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;



class DocumentVectorRepository
{


    protected string $table = 'document_embedding';





    public function debugWorkspace(
        string $workspaceId
    ) {

        return Surreal::query(

            new BoundQuery(

                query: "

                    SELECT count() AS total

                    FROM {$this->table}

                    WHERE workspace_id = \$workspace_id

                    GROUP ALL;

                ",

                bindings: [

                    'workspace_id' =>
                        (string) $workspaceId

                ]

            )

        );

    }








    public function store(
        array $data
    ) {


        return Surreal::query(

            new BoundQuery(

                query: "

                    CREATE {$this->table} SET


                        document_id =
                        <string>\$document_id,


                        workspace_id =
                        <string>\$workspace_id,


                        chunk_id =
                        <string>\$chunk_id,


                        content =
                        <string>\$content,


                        embedding =
                        \$embedding,


                        metadata =
                        \$metadata,


                        created_at =
                        type::datetime(
                            \$created_at
                        );

                ",

                bindings:

                $this->preparePayload(
                    $data
                )

            )

        );


    }









    public function storeBulk(
        array $vectors
    ): void {


        foreach ($vectors as $vector) {


            $this->store(
                $vector
            );


        }


    }









    public function search(

        string $workspaceId,

        array $vector,

        int $limit = 10,

        array $documentIds = [],

        array $filters = []

    ): array {


        if (empty($vector)) {

            return [];

        }






        $bindings = [


            'workspace_id' =>
                (string)
                $workspaceId,



            'vector' =>
                array_values(

                    array_map(

                        'floatval',

                        $vector

                    )

                ),



            'limit' =>
                $limit


        ];






        $where = "

            workspace_id =
            \$workspace_id

        ";






        if (!empty($documentIds)) {


            $where .= "

                AND document_id IN \$document_ids

            ";



            $bindings['document_ids'] =

                array_map(

                    'strval',

                    $documentIds

                );


        }








        if (!empty($filters)) {


            foreach ($filters as $key => $value) {


                $binding =
                    'filter_' . $key;



                $where .= "

                    AND metadata.$key =
                    \$$binding

                ";



                $bindings[$binding] =
                    $value;


            }


        }
        $sql = "

            SELECT


                id,


                document_id,


                workspace_id,


                chunk_id,


                content,


                metadata,



                vector::similarity::cosine(

                    embedding,

                    \$vector

                )

                AS score



            FROM {$this->table}



            WHERE {$where}



            ORDER BY score DESC



            LIMIT \$limit;

        ";






        $response =

            Surreal::query(

                new BoundQuery(

                    query:
                    $sql,

                    bindings:
                    $bindings

                )

            );






        $results =
            $this->formatResults(
                $response
            );





        return $this->rankResults(
            $results
        );


    }









    protected function rankResults(
        array $results
    ): array {


        foreach ($results as &$item) {


            $score =

                (float)
                (
                    $item['score']
                    ??
                    0
                );



            $metadata =
                $item['metadata']
                ??
                [];



            $section =

                strtolower(

                    $metadata['section']
                    ??
                    ''

                );





            /*
            |--------------------------------------------------------------------------
            | Academic section boost
            |--------------------------------------------------------------------------
            */


            if (

                in_array(

                    $section,

                    [

                        'method',

                        'methodology',

                        'architecture',

                        'implementation',

                        'model',

                        'experiment',

                        'evaluation',

                        'results',

                        'conclusion'

                    ],

                    true

                )

            ) {


                $score += 0.05;


            }








            /*
            |--------------------------------------------------------------------------
            | Title boost
            |--------------------------------------------------------------------------
            */


            if (

                !empty(
                $metadata['title']
            )

            ) {


                $score += 0.02;


            }







            $item['score'] =

                min(

                    $score,

                    1

                );



        }







        usort(

            $results,

            function ($a, $b) {


                return

                    ($b['score'] ?? 0)

                    <=>

                    ($a['score'] ?? 0);


            }

        );






        return array_values(
            $results
        );


    }









    protected function preparePayload(
        array $data
    ): array {


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





            'chunk_id' =>

                (string)
                (
                    $data['chunk_id']
                    ??
                    uniqid('chunk_')
                ),





            'content' =>

                $this->normalizeContent(

                    $data['content']
                    ??
                    ''

                ),





            'embedding' =>

                array_values(

                    array_map(

                        'floatval',

                        $data['embedding']
                        ??
                        []

                    )

                ),





            'metadata' =>

                is_array(
                    $data['metadata']
                    ??
                    null
                )

                ?

                $data['metadata']

                :

                [],






            'created_at' =>

                now()
                    ->toIso8601String()


        ];


    }









    protected function normalizeContent(
        mixed $content
    ): string {


        if ($content === null) {

            return '';

        }





        if (is_array($content)) {


            $content =

                json_encode(

                    $content,

                    JSON_UNESCAPED_UNICODE

                );


        }






        return trim(

            preg_replace(

                '/\s+/u',

                ' ',

                str_replace(

                    [

                        "\r",

                        "\n",

                        "\t"

                    ],

                    ' ',

                    (string)
                    $content

                )

            )

        );


    }
    protected function formatResults(
        mixed $response
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

            isset(
            $response[0]['result']
        )

            &&

            is_array(
                $response[0]['result']
            )

        ) {


            return

                collect(
                    $response[0]['result']
                )

                    ->map(

                        function ($item) {


                            $item['metadata'] =

                                $item['metadata']
                                ??
                                [];



                            return $item;


                        }

                    )

                    ->values()

                    ->all();


        }







        return collect($response)

            ->map(

                function ($item) {


                    $item['metadata'] =

                        $item['metadata']
                        ??
                        [];



                    return $item;


                }

            )

            ->values()

            ->all();


    }









    public function deleteDocument(
        string $documentId
    ) {


        return Surreal::query(

            new BoundQuery(

                query: "

                    DELETE FROM {$this->table}

                    WHERE document_id =
                    \$document_id;

                ",

                bindings: [


                    'document_id' =>

                        (string)
                        $documentId


                ]

            )

        );


    }

    public function searchMetadata(
        string $workspaceId,
        string $field,
        string $value,
        int $limit = 5
    ): array {


        $response =
            Surreal::query(

                new BoundQuery(

                    query: "

                    SELECT

                        id,

                        document_id,

                        workspace_id,

                        content,

                        metadata


                    FROM {$this->table}


                    WHERE

                        workspace_id =
                        \$workspace_id


                    AND

                        string::lowercase(
                            metadata.$field
                        )

                        CONTAINS

                        string::lowercase(
                            \$value
                        )


                    LIMIT \$limit;

                ",

                    bindings: [


                        'workspace_id' =>
                            (string) $workspaceId,


                        'value' =>
                            $value,


                        'limit' =>
                            $limit


                    ]

                )

            );


        return $this->formatResults(
            $response
        );

    }
}
