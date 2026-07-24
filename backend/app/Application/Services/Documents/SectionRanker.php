<?php

namespace App\Application\Services\Documents;


class SectionRanker
{


    public function rank(

        array $chunks,

        array $sectionWeights

    ): array {



        foreach($chunks as &$chunk){


            $section = strtolower(

                $chunk['metadata']['section']
                ??
                ''

            );



            $section = $this->normalizeSection(
                $section
            );



            $boost =

                $sectionWeights[$section]
                ??
                0;



            $semanticScore =

                (float)
                (
                    $chunk['score']
                    ??
                    0
                );



            /**
             * Combine semantic similarity
             * with planner preference
             *
             * 70% semantic
             * 30% planner
             */

            $chunk['planner_score'] =

                (
                    $semanticScore * 0.7
                )

                +

                (
                    ($boost / 100) * 0.3
                );



            $chunk['metadata']['normalized_section']
                =
                $section;


        }



        unset($chunk);



        usort(

            $chunks,

            function($a,$b){


                return

                    $b['planner_score']

                    <=>

                    $a['planner_score'];

            }

        );



        return $this->removeDuplicateSections(
            $chunks
        );


    }





    protected function normalizeSection(

        string $section

    ): string {



        return match($section){


            'method',
            'methods',
            'methodology'
                =>
                'methodology',



            'model',
            'models',
            'architecture'
                =>
                'methodology',



            'intro',
            'introduction'
                =>
                'introduction',



            'abstract'
                =>
                'abstract',



            'conclusion',
            'conclusions'
                =>
                'conclusion',



            default
                =>
                $section

        };


    }





    protected function removeDuplicateSections(

        array $chunks

    ): array {



        $seen = [];

        $result = [];



        foreach($chunks as $chunk){


            $section =

                $chunk['metadata']['normalized_section']
                ??
                '';



            /**
             * Keep only first
             * highest ranked chunk
             * from each section
             */

            if(
                isset($seen[$section])
                &&
                $section !== ''
            ){

                continue;

            }



            $seen[$section] = true;


            $result[] = $chunk;


        }



        return $result;


    }



}
