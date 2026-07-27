<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;



class DocumentEmbeddingRepository
{


    protected string $table = 'document_embedding';








    /**
     * Store embedding
     */
    public function store(
        array $data
    ): array {


        $response =

            Surreal::query(

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
                            ),


                            updated_at =
                            time::now();


                    ",

                    bindings:

                        $this->preparePayload(
                            $data
                        )

                )

            );





        return $this->format(
            $response
        );


    }









    /**
     * Store multiple embeddings
     */
    public function storeBulk(
        array $vectors
    ): void {


        foreach($vectors as $vector){


            $this->store(
                $vector
            );


        }


    }









    /**
     * Count workspace embeddings
     */
    public function countWorkspace(
        string $workspaceId
    ): int {


        $response =

            Surreal::query(

                new BoundQuery(

                    query: "

                        SELECT count() AS total

                        FROM {$this->table}

                        WHERE workspace_id =
                        \$workspace_id

                        GROUP ALL;


                    ",

                    bindings:[


                        'workspace_id'=>

                            (string)
                            $workspaceId


                    ]

                )

            );





        $result =
            $this->format(
                $response
            );





        return (int)
            (
                $result[0]['total']
                ??
                0
            );


    }









    /**
     * Find embeddings by document
     */
    public function findByDocument(
        string $documentId
    ): array {


        $response =

            Surreal::query(

                new BoundQuery(

                    query: "

                        SELECT *

                        FROM {$this->table}

                        WHERE document_id =
                        \$document_id;


                    ",

                    bindings:[


                        'document_id'=>

                            (string)
                            $documentId


                    ]

                )

            );





        return $this->format(
            $response
        );


    }









    /**
     * Delete document embeddings
     */
    public function deleteDocument(
        string $documentId
    ): mixed {


        return Surreal::query(

            new BoundQuery(

                query: "

                    DELETE FROM {$this->table}

                    WHERE document_id =
                    \$document_id;


                ",

                bindings:[


                    'document_id'=>

                        (string)
                        $documentId


                ]

            )

        );


    }









    /**
     * Prepare payload
     */
    protected function preparePayload(
        array $data
    ): array {


        return [


            'document_id'=>

                (string)
                (
                    $data['document_id']
                    ??
                    ''
                ),




            'workspace_id'=>

                (string)
                (
                    $data['workspace_id']
                    ??
                    ''
                ),




            'chunk_id'=>

                (string)
                (
                    $data['chunk_id']
                    ??
                    ''
                ),




            'content'=>

                trim(

                    (string)
                    (
                        $data['content']
                        ??
                        ''
                    )

                ),




            'embedding'=>

                array_values(

                    array_map(

                        'floatval',

                        $data['embedding']
                        ??
                        []

                    )

                ),




            'metadata'=>

                is_array(
                    $data['metadata']
                    ??
                    null
                )

                ?

                $data['metadata']

                :

                [],




            'created_at'=>

                now()
                ->toISOString()


        ];


    }









    protected function format(
        mixed $response
    ): array {


        if(empty($response)){

            return [];

        }






        if(

            is_object($response)

            &&

            method_exists(
                $response,
                'getData'
            )

        ){

            $response =
                $response->getData();

        }







        if(!is_array($response)){

            return [];

        }







        if(

            isset(
                $response[0]['result']
            )

            &&

            is_array(
                $response[0]['result']
            )

        ){

            return collect(
                $response[0]['result']
            )
            ->values()
            ->all();

        }







        return collect($response)

            ->values()

            ->all();


    }



}
