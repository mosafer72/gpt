<?php

namespace App\Application\Services\Documents;


class QueryIntentClassifier
{


    public function classify(
        string $query
    ): array {


        $query =
            strtolower(
                trim($query)
            );



        $patterns = [


            'title' => [

                'title',
                'name of article',
                'paper name',
                'article name',
                'what is this paper'

            ],



            'abstract' => [

                'abstract',
                'summary',
                'overview',
                'brief'

            ],



            'methodology' => [

                'method',
                'methodology',
                'approach',
                'architecture',
                'model',
                'framework',
                'algorithm'

            ],



            'dataset' => [

                'dataset',
                'data',
                'training data',
                'test data',
                'benchmark',
                'corpus'

            ],



            'experiment' => [

                'experiment',
                'experimental setup',
                'setup',
                'configuration',
                'environment'

            ],



            'result' => [

                'result',
                'results',
                'accuracy',
                'performance',
                'evaluation',
                'comparison',
                'score',
                'metric'

            ],



            'conclusion' => [

                'conclusion',
                'future work',
                'limitation',
                'limitation',
                'discussion'

            ]

        ];





        $matched = [];




        foreach ($patterns as $intent => $words) {



            foreach ($words as $word) {



                if (
                    str_contains(
                        $query,
                        $word
                    )
                ) {


                    $matched[] = $intent;


                    break;


                }


            }


        }






        if(empty($matched)){


            return [

                'primary' => 'general',

                'sections' => [

                    'abstract',

                    'introduction',

                    'methodology',

                    'results',

                    'conclusion'

                ]

            ];


        }






        return [

            /**
             * First matched intent
             */
            'primary' =>
                $matched[0],



            /**
             * All relevant sections
             */
            'sections' =>
                $this->mapSections(
                    $matched
                )

        ];



    }







    protected function mapSections(

        array $intents

    ): array {


        $map = [


            'title' => [

                'abstract'

            ],



            'abstract' => [

                'abstract'

            ],



            'methodology' => [

                'methodology',

                'architecture',

                'model'

            ],



            'dataset' => [

                'dataset',

                'implementation'

            ],



            'experiment' => [

                'experiment',

                'experiments'

            ],



            'result' => [

                'results',

                'evaluation'

            ],



            'conclusion' => [

                'discussion',

                'conclusion',

                'future work'

            ]

        ];





        $sections = [];



        foreach($intents as $intent){


            foreach(
                $map[$intent] ?? []
                as $section
            ){

                $sections[] = $section;

            }

        }




        return array_values(
            array_unique(
                $sections
            )
        );


    }


}
