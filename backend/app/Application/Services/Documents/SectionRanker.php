<?php

namespace App\Application\Services\Documents;


class SectionRanker
{


    public function rank(

        array $chunks,

        array $sectionWeights

    ): array {


        foreach ($chunks as &$chunk) {


            $section = strtolower(

                $chunk['metadata']['section']
                ??
                ''

            );



            $normalized = $this->normalizeSection(
                $section
            );



            $plannerWeight =

                (float)
                (
                    $sectionWeights[$normalized]
                    ??
                    0
                );



            /**
             * Vector similarity
             */
            $semanticScore =

                (float)
                (
                    $chunk['score']
                    ??
                    0
                );



            /**
             * Final score
             *
             * Semantic 75%
             * Planner 25%
             */

            $chunk['planner_score'] =

                ($semanticScore * 0.75)

                +

                ($plannerWeight * 0.25);



            $chunk['metadata']['normalized_section']
                =
                $normalized;


        }


        unset($chunk);




        usort(

            $chunks,

            function ($a, $b) {

                return

                    ($b['planner_score'] ?? 0)

                    <=>

                    ($a['planner_score'] ?? 0);

            }

        );



        return $this->diversifySections(
            $chunks
        );


    }







    protected function normalizeSection(

        string $section

    ): string {


        return match ($section) {


            'method',
            'methods',
            'methodology',
            'approach'
            =>
            'methodology',



            'model',
            'architecture'
            =>
            'architecture',



            'experiment',
            'experiments'
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
            'conclusions'
            =>
            'conclusion',



            default
            =>
            trim($section)

        };


    }








    /**
     * Keep diversity but avoid flooding
     */
    protected function diversifySections(

        array $chunks

    ): array {


        $sectionCount = [];

        $result = [];



        foreach ($chunks as $chunk) {


            $section =

                $chunk['metadata']['normalized_section']
                ??
                '';



            if ($section === '') {

                continue;

            }



            $sectionCount[$section] =
                $sectionCount[$section] ?? 0;



            /**
             * Maximum 3 chunks
             * from same section
             */

            if (
                $sectionCount[$section] >= 3
            ) {

                continue;

            }



            $result[] = $chunk;


            $sectionCount[$section]++;


        }



        return $result;


    }


}
