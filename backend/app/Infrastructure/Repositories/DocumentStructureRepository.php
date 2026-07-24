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







    public function findByDocument(
        string $documentId
    ) {


        return Surreal::query(

            new BoundQuery(

                query:

                "
                SELECT *

                FROM {$this->table}

                WHERE document_id =
                \$document_id;

                ",

                bindings: [

                    'document_id' => $documentId

                ]

            )

        );


    }


}
