<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;



class DocumentSectionTranslationRepository
{


    protected string $table =
        'document_section_translation';




    public function getByDocument(

        string $documentId,

        string $workspaceId

    ): array {


        $sql = "

        SELECT *

        FROM {$this->table}

        WHERE

            document_id = \$document

        AND

            workspace_id = \$workspace


        ORDER BY

            metadata.order ASC;


        ";



        $response =

            Surreal::query(

                new BoundQuery(

                    query: $sql,


                    bindings: [

                        'document' =>
                            (string) $documentId,


                        'workspace' =>
                            (string) $workspaceId

                    ]

                )

            );




        return $this->formatResults(
            $response
        );

    }







    protected function formatResults(
        mixed $response
    ): array {


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



        /*
        |--------------------------------------------------------------------------
        | SurrealDB nested response
        |--------------------------------------------------------------------------
        */

        if (
            isset($response[0])
            &&
            is_array($response[0])
        ) {

            $result = $response[0];


            // اگر دوباره تو در تو بود
            if (
                isset($result[0])
                &&
                is_array($result[0])
                &&
                isset($result[0][0])
            ) {

                return $result[0];

            }


            return $result;

        }



        return [];

    }
    public function findByDocument(
        string $documentId
    ): array {


        $sql = "

        SELECT *

        FROM document_section_translation

        WHERE document_id = \$document_id;

    ";


        $response =
            Surreal::query(

                new BoundQuery(

                    query: $sql,

                    bindings: [

                        'document_id' =>
                            (string) $documentId

                    ]

                )

            );



        return $this->formatResults(
            $response
        );

    }

    public function store(
        array $data
    ) {



        \Log::info(
            'DOCUMENT SECTION TRANSLATION STORE',
            [
                'document_id' =>
                    $data['document_id'] ?? null,
                'section' =>
                    $data['section'] ?? null
            ]
        );






        /*
        |--------------------------------------------------------------------------
        | Remove old translation for this section/document if exists
        |--------------------------------------------------------------------------
        */


        Surreal::query(

            new BoundQuery(

                query:

                "
                DELETE FROM {$this->table}

                WHERE document_id =
                \$document_id
                AND section =
                \$section;

                ",

                bindings: [

                    'document_id' =>
                        (string)
                        $data['document_id'],

                    'section' =>
                        (string)
                        ($data['section'] ?? 'unknown')

                ]

            )

        );







        /*
        |--------------------------------------------------------------------------
        | Create new translation record
        |--------------------------------------------------------------------------
        */


        return Surreal::query(

            new BoundQuery(

                query:

                "
                CREATE {$this->table}

                CONTENT \$payload;

                ",

                bindings: [

                    'payload' =>
                        $data

                ]

            )

        );


    }

}
