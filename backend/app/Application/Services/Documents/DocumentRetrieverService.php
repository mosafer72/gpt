<?php

namespace App\Application\Services\Documents;


use App\Application\Services\Ai\EmbeddingService;
use App\Domain\Contracts\DocumentRetriever;
use App\Infrastructure\Repositories\DocumentVectorRepository;
use App\Infrastructure\Repositories\DocumentStructureRepository;
use Illuminate\Support\Facades\Log;



class DocumentRetrieverService implements DocumentRetriever
{


    public function __construct(

        protected EmbeddingService $embeddingService,

        protected DocumentVectorRepository $vectorRepository,

        protected DocumentStructureRepository $structureRepository,

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


        if(blank($query)) {

            return $this->emptyResult();

        }







        /*
        |--------------------------------------------------------------------------
        | 1. Query Intent
        |--------------------------------------------------------------------------
        */


        $intent =
            $this->classifier
                ->classify(
                    $query
                );





        Log::info(
            'DOCUMENT QUERY INTENT',
            [

                'intent'=>$intent,

                'query'=>$query

            ]
        );









        /*
        |--------------------------------------------------------------------------
        | 2. Load Real Sections From Structure
        |--------------------------------------------------------------------------
        */


        $sections = [];





        foreach($documentIds as $documentId){


            $structure =
                $this->structureRepository
                    ->findByDocument(
                        (string)$documentId
                    );



            if(
                !empty($structure['sections'])
            ){

                $sections = array_merge(
                    $sections,
                    $structure['sections']
                );

            }


        }







        /*
        |--------------------------------------------------------------------------
        | 3. Ask Planner
        |--------------------------------------------------------------------------
        */


        $sectionPlan =
            $this->planner
                ->plan(

                    intent:$intent,

                    sections:$sections,

                    query:$query

                );






        Log::info(
            'DOCUMENT PLANNER RESULT',
            [

                'plan'=>$sectionPlan

            ]
        );









        /*
        |--------------------------------------------------------------------------
        | 4. Metadata Question
        |--------------------------------------------------------------------------
        */


        if(
            ($intent['type'] ?? null)
            ===
            'title'
        ){

            return $this->metadataResult();


        }









        /*
        |--------------------------------------------------------------------------
        | 5. Query Embedding
        |--------------------------------------------------------------------------
        */


        $vector =
            $this->embeddingService
                ->embedQuery(
                    $query
                );





        if(empty($vector)){

            return $this->emptyResult();

        }









        /*
        |--------------------------------------------------------------------------
        | 6. Vector Search
        |--------------------------------------------------------------------------
        */


        $candidates =
            $this->vectorRepository
                ->search(

                    workspaceId:$workspaceId,

                    vector:$vector,

                    limit:max(
                        $limit * 5,
                        25
                    ),

                    documentIds:$documentIds

                );









        $candidates =
            collect($candidates)

                ->flatten(1)

                ->filter(
                    fn($item)=>
                    is_array($item)
                )

                ->values()

                ->all();









        /*
        |--------------------------------------------------------------------------
        | 7. Cleaning
        |--------------------------------------------------------------------------
        */


        $results =
            collect($candidates)

                ->filter(

                    fn($item)=>

                    !empty($item['content'])

                    &&

                    ($item['score'] ?? 0) >= 0.30

                )

                ->unique(

                    fn($item)=>

                    md5(
                        trim(
                            $item['content']
                        )
                    )

                )

                ->values()

                ->all();









        /*
        |--------------------------------------------------------------------------
        | 8. Academic Ranking
        |--------------------------------------------------------------------------
        */


        if(!empty($results)){


            $results =
                $this->ranker
                    ->rank(

                        $results,

                        $sectionPlan

                    );


        }









        /*
        |--------------------------------------------------------------------------
        | 9. Final Score
        |--------------------------------------------------------------------------
        */


        $results =

            collect($results)

                ->map(

                    function($item){



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






                        if(
                            in_array(

                                $section,

                                [

                                    'references',

                                    'bibliography',

                                    'appendix'

                                ],

                                true

                            )
                        ){

                            $score -=0.20;

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

                    fn($item)=>

                    $item['final_score']

                )


                ->take($limit)


                ->values()


                ->all();









        /*
        |--------------------------------------------------------------------------
        | 10. Context
        |--------------------------------------------------------------------------
        */


        $context =
            $this->contextBuilder
                ->build(
                    $results
                );






        return [

            'chunks'=>$results,


            'context'=>$context,


            'text'=>

                $this->contextBuilder
                    ->toText(
                        $context
                    )

        ];


    }









    protected function metadataResult(): array
    {

        return [

            'chunks'=>[],


            'context'=>[

                'metadata_request'=>true

            ],


            'text'=>

                'Metadata request detected'

        ];

    }









    protected function emptyResult(): array
    {

        return [

            'chunks'=>[],


            'context'=>'',


            'text'=>''

        ];

    }



}
