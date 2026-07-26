<?php

namespace App\Application\Services\Documents;


use App\Application\Services\Ai\EmbeddingService;
use App\Domain\Contracts\DocumentRetriever;
use App\Infrastructure\Repositories\DocumentVectorRepository;
use Illuminate\Support\Facades\Log;



class DocumentRetrieverService implements DocumentRetriever
{


    public function __construct(

        protected EmbeddingService $embeddingService,

        protected DocumentVectorRepository $vectorRepository,

        protected QueryIntentClassifier $classifier,

        protected SectionPlanner $planner,

        protected SectionRanker $ranker,

        protected DocumentContextBuilder $contextBuilder

    ) {
    }







    public function retrieve(

        string $workspaceId,

        string $query,

        int $limit = 5,

        array $documentIds = []

    ): array {


        if (
            blank($query)
        ) {


            return [

                'chunks' => [],

                'context' => '',

                'text' => ''

            ];


        }







        /*
        |--------------------------------------------------------------------------
        | Query Classification
        |--------------------------------------------------------------------------
        */


        $intent =
            $this->classifier
                ->classify(
                    $query
                );





        $sectionPlan =
            $this->planner
                ->plan(
                    $intent
                );







        Log::info(
            'DOCUMENT QUERY PLAN',
            [

                'intent' =>
                    $intent,


                'sections' =>
                    $sectionPlan,


                'workspace' =>
                    $workspaceId,


                'documents' =>
                    $documentIds

            ]
        );








        /*
        |--------------------------------------------------------------------------
        | Query Embedding
        |--------------------------------------------------------------------------
        */


        $vector =
            $this->embeddingService
                ->embedQuery(
                    $query
                );






        if (
            empty($vector)
        ) {


            return [

                'chunks' => [],

                'context' => '',

                'text' => ''

            ];


        }








        /*
        |--------------------------------------------------------------------------
        | Vector Search
        |--------------------------------------------------------------------------
        */


        $candidates =
            $this->vectorRepository
                ->search(

                    workspaceId:

                    $workspaceId,


                    vector:

                    $vector,


                    limit:

                    max(
                        $limit * 5,
                        25
                    ),


                    documentIds:

                    $documentIds

                );







        /*
        |--------------------------------------------------------------------------
        | Normalize SurrealDB Result
        |--------------------------------------------------------------------------
        */


        $candidates =
            collect($candidates)

                ->flatten(1)

                ->filter(
                    fn($item) =>
                    is_array($item)
                )

                ->values()

                ->all();







        Log::info(
            'DOCUMENT VECTOR SEARCH',
            [

                'count' =>
                    count($candidates)

            ]
        );








        /*
        |--------------------------------------------------------------------------
        | Cleaning + Deduplication
        |--------------------------------------------------------------------------
        */


        $results =

            collect($candidates)

                ->filter(

                    function ($item) {


                        return

                            !empty($item['content'])

                            &&

                            ($item['score'] ?? 0) >= 0.35;


                    }

                )


                ->unique(

                    function ($item) {


                        return md5(

                            trim(
                                $item['content']
                            )

                        );


                    }

                )


                ->values()

                ->all();









        /*
        |--------------------------------------------------------------------------
        | Academic Section Ranking
        |--------------------------------------------------------------------------
        */


        if (
            !empty($results)
        ) {


            $results =
                $this->ranker
                    ->rank(

                        $results,

                        $sectionPlan

                    );


        }








        /*
        |--------------------------------------------------------------------------
        | Final Score
        |--------------------------------------------------------------------------
        */


        $results =

            collect($results)

                ->map(

                    function ($item) {


                        $score =

                            (float)

                            (

                                $item['planner_score']

                                ??

                                $item['score']

                                ??

                                0

                            );





                        $section =

                            strtolower(

                                $item['metadata']['section']

                                ??

                                ''

                            );







                        if (

                            in_array(

                                $section,

                                [

                                    'reference',

                                    'references',

                                    'bibliography'

                                ],

                                true

                            )

                        ) {


                            $score -= 0.20;


                        }






                        if (

                            in_array(

                                $section,

                                [

                                    'abstract',

                                    'introduction',

                                    'method',

                                    'methodology',

                                    'experiment',

                                    'results',

                                    'conclusion'

                                ],

                                true

                            )

                        ) {


                            $score += 0.03;


                        }







                        $item['final_score'] =

                            max(

                                0,

                                min(

                                    $score,

                                    1

                                )

                            );






                        return $item;


                    }

                )



                ->sortByDesc(

                    fn($item) =>

                    $item['final_score']

                )



                ->take($limit)



                ->values()



                ->all();









        Log::info(
            'FINAL DOCUMENT RESULTS',
            [

                'count' =>
                    count($results),


                'sections' =>
                    collect($results)
                        ->pluck(
                            'metadata.section'
                        )
                        ->toArray(),


                'scores' =>
                    collect($results)
                        ->pluck(
                            'final_score'
                        )
                        ->toArray()

            ]
        );









        /*
        |--------------------------------------------------------------------------
        | Context Build
        |--------------------------------------------------------------------------
        */


        $context =

            $this->contextBuilder

                ->build(

                    $results

                );






        return [

            'chunks' =>

                $results,



            'context' =>

                $context,



            'text' =>

                $this->contextBuilder

                    ->toText(
                        $context
                    )

        ];


    }


}
