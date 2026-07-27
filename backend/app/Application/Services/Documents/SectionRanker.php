<?php

namespace App\Application\Services\Documents;


class SectionRanker
{


    public function rank(

        array $chunks,

        array $sectionPlan

    ): array {


        /*
        |--------------------------------------------------------------------------
        | Extract Planner Sections
        |--------------------------------------------------------------------------
        */


        $weights =

            $sectionPlan['sections']
            ??

            $sectionPlan;






        foreach ($chunks as &$chunk) {



            $section =

                strtolower(

                    trim(

                        $chunk['metadata']['section']
                        ??
                        ''

                    )

                );






            $normalized =

                $this->normalizeSection(
                    $section
                );







            /*
            |--------------------------------------------------------------------------
            | Planner Score Normalize
            |--------------------------------------------------------------------------
            */


            $plannerScore =

                (float)
                (
                    $weights[$normalized]
                    ??
                    0
                );



            $plannerScore =

                $plannerScore / 100;








            /*
            |--------------------------------------------------------------------------
            | Semantic Similarity
            |--------------------------------------------------------------------------
            */


            $semanticScore =

                (float)
                (
                    $chunk['score']
                    ??
                    0
                );







            /*
            |--------------------------------------------------------------------------
            | Importance Bonus
            |--------------------------------------------------------------------------
            */


            $importanceBonus =

                $this->importanceScore(

                    $chunk['metadata']['importance']
                    ??
                    ''

                );







            /*
            |--------------------------------------------------------------------------
            | Final Ranking
            |--------------------------------------------------------------------------
            */


            $chunk['planner_score'] =

                ($semanticScore * 0.70)

                +

                ($plannerScore * 0.25)

                +

                ($importanceBonus * 0.05);







            $chunk['metadata']['normalized_section']
                =
                $normalized;



        }



        unset($chunk);








        usort(

            $chunks,

            function($a,$b){


                return

                    ($b['planner_score'] ?? 0)

                    <=>

                    ($a['planner_score'] ?? 0);


            }

        );







        return

            $this->diversifySections(
                $chunks
            );


    }









    protected function normalizeSection(

        string $section

    ): string {


        return match($section){


            'method',

            'methods',

            'methodology',

            'approach'

            =>

            'methodology',






            'model',

            'architecture',

            'framework'

            =>

            'architecture',






            'experiment',

            'experiments',

            'experimental setup'

            =>

            'experiments',






            'evaluation'

            =>

            'evaluation',






            'intro',

            'introduction'

            =>

            'introduction',






            'abstract'

            =>

            'abstract',






            'result',

            'results'

            =>

            'results',






            'conclusion',

            'conclusions',

            'future work'

            =>

            'conclusion',






            default

            =>

            trim($section)



        };


    }









    protected function importanceScore(

        string $importance

    ): float {


        return match(

            strtolower(
                $importance
            )

        ){


            'very_high'

            =>

            1.0,



            'high'

            =>

            0.7,



            'medium'

            =>

            0.4,



            'low'

            =>

            0.1,



            default

            =>

            0.3


        };


    }









    protected function diversifySections(

        array $chunks

    ): array {


        $count = [];

        $result = [];






        foreach($chunks as $chunk){



            $section =

                $chunk['metadata']['normalized_section']
                ??
                'general';





            $count[$section] =

                $count[$section]
                ??
                0;







            /*
            |--------------------------------------------------------------------------
            | Maximum chunks per section
            |--------------------------------------------------------------------------
            */


            if(
                $count[$section] >= 3
            ){

                continue;

            }







            $result[] = $chunk;


            $count[$section]++;



        }






        return $result;


    }


}
