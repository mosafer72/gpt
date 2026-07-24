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

        protected SectionRanker $ranker

    ) {

    }



    public function retrieve(

        string $workspaceId,

        string $query,

        int $limit = 5,

        array $documentIds = []

    ): array {


        if (blank($query)) {

            return [];

        }



        /*
        |--------------------------------------------------------------------------
        | Intent + Planner
        |--------------------------------------------------------------------------
        */


        $intent =
            $this->classifier
                ->classify($query);



        $sectionPlan =
            $this->planner
                ->plan($intent);



        Log::info(
            'ACADEMIC PLANNER RESULT',
            [
                'intent' => $intent,
                'sections' => $sectionPlan
            ]
        );



        Log::info(
            'ACADEMIC RETRIEVER SEARCH',
            [
                'workspace' => $workspaceId,
                'query' => $query,
                'documents' => $documentIds
            ]
        );



        /*
        |--------------------------------------------------------------------------
        | Query Embedding
        |--------------------------------------------------------------------------
        */


        $queryVector =
            $this->embeddingService
                ->embedQuery($query);



        Log::debug(
            'QUERY VECTOR',
            [
                'dimension' => count($queryVector)
            ]
        );



        if (empty($queryVector)) {

            return [];

        }




        /*
        |--------------------------------------------------------------------------
        | Vector Retrieval
        |--------------------------------------------------------------------------
        */


        $candidates =
            $this->vectorRepository
                ->search(

                    workspaceId: $workspaceId,

                    vector: $queryVector,

                    limit: max(
                        $limit * 5,
                        25
                    ),

                    documentIds: $documentIds

                );




        /*
        |--------------------------------------------------------------------------
        | FIX:
        | SurrealDB SDK can return nested result arrays
        |--------------------------------------------------------------------------
        */


        $candidates =
            collect($candidates)
                ->flatten(1)
                ->filter(
                    fn($item) => is_array($item)
                )
                ->values()
                ->all();



        Log::info(
            'VECTOR RESULTS',
            [
                'count' => count($candidates),
                'first' => $candidates[0] ?? null
            ]
        );





        /*
        |--------------------------------------------------------------------------
        | Clean Results
        |--------------------------------------------------------------------------
        */


        $results =
            collect($candidates)

                ->filter(
                    function ($item) {

                        return

                            is_array($item)

                            &&

                            !empty(
                            trim(
                                $item['content'] ?? ''
                            )
                        );

                    }
                )


                ->unique(
                    function ($item) {

                        return md5(
                            $item['content']
                        );

                    }
                )
                ->filter(function ($item) {

                    return
                        ($item['score'] ?? 0) > 0.35;

                })


                ->values()

                ->all();




        Log::info(
            'AFTER CLEAN RESULTS',
            [
                'count' => count($results),
                'sections' => collect($results)
                    ->pluck('metadata.section')
                    ->toArray()
            ]
        );






        /*
        |--------------------------------------------------------------------------
        | Planner Ranking
        |--------------------------------------------------------------------------
        */


        if (!empty($results)) {

            $results =
                $this->ranker
                    ->rank(

                        $results,

                        $sectionPlan

                    );

        }





        Log::info(
            'AFTER PLANNER RANKER',
            [
                'count' => count($results)
            ]
        );






        /*
        |--------------------------------------------------------------------------
        | Final Ranking
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
                            min(
                                $score,
                                1
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
            'FINAL RETRIEVER RESULT',
            [
                'count' => count($results),

                'sections' =>
                    collect($results)
                        ->pluck('metadata.section'),

                'scores' =>
                    collect($results)
                        ->pluck('final_score')
            ]
        );




        return $results;


    }

}
