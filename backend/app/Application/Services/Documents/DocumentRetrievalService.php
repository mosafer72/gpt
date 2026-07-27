<?php

namespace App\Application\Services\Documents;


use App\Application\Services\Ai\EmbeddingService;

use App\Domain\Documents\Contracts\DocumentVectorRepositoryInterface;

use App\Application\DTOs\Documents\RetrievalResult;

use Illuminate\Support\Facades\Log;



class DocumentRetrievalService
{


    public function __construct(

        protected EmbeddingService $embeddingService,


        protected DocumentVectorRepositoryInterface $vectorRepository


    ) {

    }










    public function retrieve(

        string $workspaceId,

        string $question,

        int $limit = 8,

        array $filters = []

    ): array {


        /*
        |--------------------------------------------------------------------------
        | Create Query Embedding
        |--------------------------------------------------------------------------
        */


        try {


            $embedding =

                $this->embeddingService
                    ->embedDocument(
                        $question
                    );


        } catch(\Throwable $e){



            Log::warning(

                'DOCUMENT RETRIEVAL EMBEDDING FAILED',

                [

                    'error'=>
                        $e->getMessage()

                ]

            );


            return [];


        }








        if(empty($embedding)){


            return [];


        }








        /*
        |--------------------------------------------------------------------------
        | Vector Search
        |--------------------------------------------------------------------------
        */


        $results =

            $this->vectorRepository
                ->search(

                    workspaceId:$workspaceId,


                    vector:$embedding,


                    limit:$limit * 4,


                    filters:$filters

                );








        if(empty($results)){


            return [];


        }









        /*
        |--------------------------------------------------------------------------
        | Semantic Ranking
        |--------------------------------------------------------------------------
        */


        $ranked=[];







        foreach($results as $item){



            $metadata =

                $item['metadata']
                ??
                [];





            $score =

                (float)
                (
                    $item['score']
                    ??
                    0
                );









            /*
            |--------------------------------------------------------------------------
            | Academic Paper Priority
            |--------------------------------------------------------------------------
            */


            $section =

                strtolower(

                    $metadata['section']
                    ??
                    ''

                );





            if(

                in_array(

                    $section,

                    [

                        'method',

                        'methods',

                        'methodology',

                        'architecture',

                        'model',

                        'implementation'

                    ],

                    true

                )

            ){


                $score += 0.07;


            }







            if(

                in_array(

                    $section,

                    [

                        'experiment',

                        'evaluation',

                        'results',

                        'conclusion'

                    ],

                    true

                )

            ){


                $score +=0.05;


            }










            /*
            |--------------------------------------------------------------------------
            | Importance Boost
            |--------------------------------------------------------------------------
            */


            $importance =

                $metadata['importance']
                ??
                '';




            if($importance === 'very_high'){


                $score +=0.05;


            }


            elseif($importance === 'high'){


                $score +=0.03;


            }









            /*
            |--------------------------------------------------------------------------
            | Title Match Boost
            |--------------------------------------------------------------------------
            */


            if(

                !empty(
                    $metadata['title']
                )

            ){


                $score +=0.01;


            }









            $ranked[] =

                new RetrievalResult(


                    chunkId:

                        (string)
                        (
                            $item['chunk_id']
                            ??
                            ''
                        ),



                    content:

                        $item['content']
                        ??
                        '',



                    score:

                        min(

                            $score,

                            1

                        ),



                    metadata:

                        $metadata


                );



        }









        /*
        |--------------------------------------------------------------------------
        | Final Sort
        |--------------------------------------------------------------------------
        */


        usort(

            $ranked,

            function(
                $a,
                $b
            ){

                return

                    $b->score

                    <=>

                    $a->score;


            }

        );









        return array_values(

            array_slice(

                $ranked,

                0,

                $limit

            )

        );


    }



}
