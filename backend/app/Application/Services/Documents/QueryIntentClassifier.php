<?php

namespace App\Application\Services\Documents;


class QueryIntentClassifier
{


    public function classify(
        string $query
    ): string {


        $query =
            strtolower(
                $query
            );



        $patterns = [


            'title' => [

                'title',
                'name of article',
                'paper name',
                'article name'

            ],



            'abstract' => [

                'abstract',
                'summary',
                'overview'

            ],



            'methodology' => [

                'method',
                'methodology',
                'approach',
                'architecture',
                'model'

            ],



            'dataset' => [

                'dataset',
                'data',
                'training data',
                'test data'

            ],



            'experiment' => [

                'experiment',
                'setup',
                'configuration'

            ],



            'result' => [

                'result',
                'accuracy',
                'performance',
                'evaluation'

            ],



            'conclusion' => [

                'conclusion',
                'future work',
                'limitation'

            ]

        ];






        foreach ($patterns as $intent => $words) {



            foreach ($words as $word) {


                if (
                    str_contains(
                        $query,
                        $word
                    )
                ) {

                    return $intent;

                }


            }


        }




        return 'general';


    }


}
