<?php

namespace App\Application\Services\Documents;


use App\Application\Services\Ai\EmbeddingService;

use App\Application\DTOs\Documents\RetrievalResult;

use App\Domain\Documents\Contracts\DocumentVectorRepositoryInterface;

use App\Infrastructure\Repositories\DocumentChunkRepository;

use Illuminate\Support\Facades\Log;



class HybridDocumentRetrievalService
{


    public function __construct(

        protected EmbeddingService $embeddingService,


        protected DocumentVectorRepositoryInterface $vectorRepository,


        protected DocumentChunkRepository $chunkRepository


    )
    {

    }









    public function retrieve(

        string $workspaceId,

        string $question,

        int $limit = 8,

        array $filters = []

    ): array {



        /*
        |--------------------------------------------------------------------------
        | Semantic Retrieval
        |--------------------------------------------------------------------------
        */


        $semanticResults = [];



        try {



            $embedding =

                $this->embeddingService
                    ->embedDocument(
                        $question
                    );



            if(!empty($embedding)){


                $semanticResults =

                    $this->vectorRepository
                        ->search(

                            workspaceId:$workspaceId,

                            vector:$embedding,

                            limit:$limit * 5,

                            filters:$filters

                        );


            }



        }catch(\Throwable $e){



            Log::warning(

                'HYBRID VECTOR SEARCH FAILED',

                [

                    'error'=>
                        $e->getMessage()

                ]

            );


        }









        /*
        |--------------------------------------------------------------------------
        | Keyword Retrieval
        |--------------------------------------------------------------------------
        */


        $keywordResults =

            $this->chunkRepository
                ->searchContent(

                    workspaceId:$workspaceId,

                    keyword:$question,

                    limit:$limit * 3

                );









        /*
        |--------------------------------------------------------------------------
        | Merge Results
        |--------------------------------------------------------------------------
        */


        $merged=[];







        foreach($semanticResults as $item){



            $id =

                $item['chunk_id']
                ??
                md5(
                    $item['content']
                    ??
                    ''
                );



            $merged[$id]=[


                'content'=>

                    $item['content']
                    ??
                    '',


                'chunk_id'=>

                    $item['chunk_id']
                    ??
                    '',


                'metadata'=>

                    $item['metadata']
                    ??
                    [],


                'vector_score'=>

                    (float)
                    (
                        $item['score']
                        ??
                        0
                    ),


                'keyword_score'=>0


            ];



        }









        foreach($keywordResults as $item){



            $id =

                $item['id']
                ??
                md5(
                    $item['content']
                    ??
                    ''
                );





            if(!isset($merged[$id])){


                $merged[$id]=[


                    'content'=>

                        $item['content']
                        ??
                        '',


                    'chunk_id'=>

                        $item['id']
                        ??
                        '',


                    'metadata'=>

                        $item['metadata']
                        ??
                        [],


                    'vector_score'=>0,


                    'keyword_score'=>0


                ];


            }






            $merged[$id]['keyword_score']=0.8;



        }









        /*
        |--------------------------------------------------------------------------
        | Hybrid Ranking
        |--------------------------------------------------------------------------
        */


        $results=[];







        foreach($merged as $item){



            $metadata =

                $item['metadata'];





            $section =

                strtolower(

                    $metadata['section']
                    ??
                    ''

                );





            $score =

                ($item['vector_score'] * 0.65)

                +

                ($item['keyword_score'] * 0.20);









            /*
            Section Priority
            */


            if(

                in_array(

                    $section,

                    [

                        'methodology',

                        'architecture',

                        'implementation',

                        'model'

                    ],

                    true

                )

            ){

                $score +=0.10;

            }



            elseif(

                in_array(

                    $section,

                    [

                        'results',

                        'evaluation',

                        'conclusion'

                    ],

                    true

                )

            ){

                $score +=0.05;

            }









            /*
            Metadata Boost
            */


            if(

                ($metadata['importance'] ?? '')

                ===

                'very_high'

            ){

                $score +=0.05;

            }







            $results[]=

                new RetrievalResult(


                    chunkId:

                        $item['chunk_id'],


                    content:

                        $item['content'],


                    score:

                        min(
                            $score,
                            1
                        ),


                    metadata:

                        $metadata


                );



        }









        usort(

            $results,

            fn($a,$b)=>

                $b->score

                <=>

                $a->score

        );









        return array_slice(

            $results,

            0,

            $limit

        );


    }



}
