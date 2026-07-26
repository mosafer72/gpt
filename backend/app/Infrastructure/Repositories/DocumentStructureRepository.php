<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;



class DocumentStructureRepository
{


    protected string $table =
        'document_structure';







    public function store(
        array $data
    ) {



        \Log::info(
            'DOCUMENT STRUCTURE STORE',
            [
                'document_id' =>
                    $data['document_id'] ?? null
            ]
        );






        /*
        |--------------------------------------------------------------------------
        | Remove old structure
        |--------------------------------------------------------------------------
        */


        Surreal::query(

            new BoundQuery(

                query:

                "
                DELETE FROM {$this->table}

                WHERE document_id =
                \$document_id;

                ",

                bindings: [

                    'document_id' =>
                        (string)
                        $data['document_id']

                ]

            )

        );







        /*
        |--------------------------------------------------------------------------
        | Create new structure
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









    public function findByDocument(
        string $documentId
    ): array {


        $response =
            Surreal::query(

                new BoundQuery(

                    query:

                    "
                    SELECT *

                    FROM {$this->table}

                    WHERE document_id =
                    \$document_id

                    LIMIT 1;

                    ",

                    bindings: [

                        'document_id' =>
                            (string)
                            $documentId

                    ]

                )

            );




        if (empty($response)) {

            return [];

        }






        /*
        |--------------------------------------------------------------------------
        | Normalize Surreal response
        |--------------------------------------------------------------------------
        */


        if (
            isset($response[0]['result'])
            &&
            is_array(
                $response[0]['result']
            )
        ) {

            return
                $response[0]['result'][0]
                ??
                [];

        }



        return $response[0] ?? [];

    }








    public function deleteByDocument(
        string $documentId
    ) {


        return Surreal::query(

            new BoundQuery(

                query:

                "
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


}
