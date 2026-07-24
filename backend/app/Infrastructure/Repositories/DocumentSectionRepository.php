<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;



class DocumentSectionRepository
{


    protected string $table =
        'document_section';





    public function store(
        array $data
    ) {


        $payload = [


            'document_id'
            =>
                (string)
                $data['document_id'],



            'workspace_id'
            =>
                (string)
                $data['workspace_id'],



            'section'
            =>
                $data['section'],



            'title'
            =>
                $data['title']
                ??
                '',



            'summary'
            =>
                $data['summary']
                ??
                '',



            'embedding'
            =>
                array_values(
                    $data['embedding']
                ),



            'chunk_ids'
            =>
                $data['chunk_ids']
                ??
                [],



            'metadata'
            =>
                $data['metadata']
                ??
                [],



            'created_at'
            =>
                now()->toISOString()

        ];






        return Surreal::query(

            new BoundQuery(

                query:

                "
                CREATE {$this->table}
                CONTENT \$payload;
                ",

                bindings:[
                    'payload'=>$payload
                ]

            )

        );


    }









    public function search(

        string $workspaceId,

        array $vector,

        int $limit = 3

    ): array {



        $query = "

        SELECT

            id,

            document_id,

            section,

            title,

            summary,

            chunk_ids,

            metadata,


            vector::similarity::cosine(
                embedding,
                \$vector
            )
            AS score


        FROM {$this->table}


        WHERE

            workspace_id =
            \$workspace_id


        ORDER BY score DESC


        LIMIT \$limit;


        ";






        $response =
            Surreal::query(

                new BoundQuery(

                    query:$query,

                    bindings:[

                        'workspace_id'
                            =>
                            (string)$workspaceId,


                        'vector'
                            =>
                            array_values($vector),


                        'limit'
                            =>
                            $limit

                    ]

                )

            );





        return $this->format(
            $response
        );


    }








    protected function format(
        $response
    ):array {



        if(
            is_object($response)
            &&
            method_exists(
                $response,
                'getData'
            )
        ){

            $data =
                $response->getData();



            return
                $data[0]['result']
                ??
                [];

        }





        return [];

    }



}
