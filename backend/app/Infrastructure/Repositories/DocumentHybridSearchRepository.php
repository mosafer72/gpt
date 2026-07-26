<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;



class DocumentHybridSearchRepository
{


    public function search(
        string $workspaceId,
        array $vector,
        int $limit = 10
    ): array {


        $results = [];



        /*
        |--------------------------------------------------------------------------
        | Original Chunks
        |--------------------------------------------------------------------------
        */


        $original =
            Surreal::query(

                new BoundQuery(

                    query: "

                    SELECT

                        id,
                        document_id,
                        content,
                        metadata,

                        vector::similarity::cosine(
                            embedding,
                            \$vector
                        )
                        AS score


                    FROM document_embedding


                    WHERE workspace_id =
                    \$workspace_id


                    ORDER BY score DESC


                    LIMIT \$limit;

                    ",

                    bindings:[

                        'workspace_id'=>
                        $workspaceId,


                        'vector'=>
                        $vector,


                        'limit'=>
                        $limit

                    ]

                )

            );





        /*
        |--------------------------------------------------------------------------
        | Persian Translation
        |--------------------------------------------------------------------------
        */


        $translated =
            Surreal::query(

                new BoundQuery(

                    query: "

                    SELECT

                        id,
                        document_id,

                        translated_content,

                        summary_fa,

                        metadata,


                        vector::similarity::cosine(
                            embedding,
                            \$vector
                        )
                        AS score


                    FROM document_section_translation


                    WHERE workspace_id =
                    \$workspace_id


                    ORDER BY score DESC


                    LIMIT \$limit;


                    ",

                    bindings:[

                        'workspace_id'=>
                        $workspaceId,


                        'vector'=>
                        $vector,


                        'limit'=>
                        $limit

                    ]

                )

            );





        $results = array_merge(

            $this->format($original),

            $this->format($translated)

        );






        /*
        |--------------------------------------------------------------------------
        | Final Ranking
        |--------------------------------------------------------------------------
        */


        usort(

            $results,

            fn($a,$b)=>

            ($b['score'] ?? 0)

            <=>

            ($a['score'] ?? 0)

        );




        return array_slice(

            $results,

            0,

            $limit

        );


    }







    protected function format(
        $data
    ):array
    {


        if(
            isset($data[0]['result'])
        ){

            return $data[0]['result'];

        }


        return [];

    }



}
