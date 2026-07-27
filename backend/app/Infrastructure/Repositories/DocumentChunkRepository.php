<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;

use SurrealDB\SDK\Query\BoundQuery;



class DocumentChunkRepository
{


    protected string $table = 'document_chunk';









    public function store(
        array $data
    ): array {


        $response =


            Surreal::query(

                new BoundQuery(

                    query: "


                    CREATE {$this->table}

                    SET


                        document_id =
                        <string>\$document_id,


                        workspace_id =
                        <string>\$workspace_id,


                        chunk_index =
                        <int>\$chunk_index,


                        content =
                        <string>\$content,


                        tokens =
                        <int>\$tokens,


                        metadata =
                        \$metadata,


                        created_at =
                        time::now()


                    RETURN AFTER;


                    ",

                    bindings:[


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



                        'chunk_index'=>

                            (int)
                            (
                                $data['chunk_index']
                                ??
                                0
                            ),



                        'content'=>

                            (string)
                            (
                                $data['content']
                                ??
                                ''
                            ),



                        'tokens'=>

                            (int)
                            (
                                $data['tokens']
                                ??
                                0
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

                            []



                    ]

                )

            );





        return $this->format(
            $response
        );


    }









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
                    \$document_id


                    ORDER BY chunk_index ASC;


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









    public function findByWorkspace(
        string $workspaceId
    ): array {


        $response =


            Surreal::query(

                new BoundQuery(

                    query: "


                    SELECT *

                    FROM {$this->table}


                    WHERE workspace_id =
                    \$workspace_id


                    ORDER BY created_at DESC;


                    ",

                    bindings:[


                        'workspace_id'=>

                            (string)
                            $workspaceId


                    ]

                )

            );





        return $this->format(
            $response
        );


    }









    public function find(
        string $id
    ): array {


        $response =


            Surreal::query(

                new BoundQuery(

                    query: "


                    SELECT *

                    FROM {$this->table}


                    WHERE id =
                    type::thing(
                        \$id
                    )


                    LIMIT 1;


                    ",

                    bindings:[


                        'id'=>

                            (string)
                            $id


                    ]

                )

            );





        $result =

            $this->format(
                $response
            );





        return $result[0] ?? [];

    }









    public function countByDocument(
        string $documentId
    ): int {


        $response =


            Surreal::query(

                new BoundQuery(

                    query: "


                    SELECT count() AS total

                    FROM {$this->table}


                    WHERE document_id =
                    \$document_id


                    GROUP ALL;


                    ",

                    bindings:[


                        'document_id'=>

                            (string)
                            $documentId


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









    public function searchContent(

        string $workspaceId,

        string $keyword,

        int $limit = 10

    ): array {


        $response =


            Surreal::query(

                new BoundQuery(

                    query: "


                    SELECT *


                    FROM {$this->table}


                    WHERE


                        workspace_id =
                        \$workspace_id


                    AND


                        string::lowercase(content)

                        CONTAINS

                        string::lowercase(
                            \$keyword
                        )


                    LIMIT \$limit;


                    ",

                    bindings:[


                        'workspace_id'=>

                            (string)
                            $workspaceId,



                        'keyword'=>

                            $keyword,



                        'limit'=>

                            $limit


                    ]

                )

            );





        return $this->format(
            $response
        );


    }









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

            isset($response[0]['result'])

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
