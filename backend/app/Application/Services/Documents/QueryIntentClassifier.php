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
                'paper name',
                'article name',
                'name of article',
                'what is this paper',
                'عنوان مقاله',
                'نام مقاله'

            ],



            'summary' => [

                'summary',
                'overview',
                'brief',
                'summarize',
                'خلاصه',
                'مرور'

            ],



            'section_specific' => [

                'method',
                'methodology',
                'architecture',
                'model',
                'dataset',
                'experiment',
                'result',
                'conclusion',
                'section',
                'بخش',
                'قسمت'

            ],



            'comparison' => [

                'compare',
                'comparison',
                'difference',
                'versus',
                'مقایسه',
                'تفاوت'

            ],



            'explanation' => [

                'explain',
                'describe',
                'how',
                'why',
                'توضیح',
                'چگونه'

            ]

        ];






        $matched = [];





        foreach ($patterns as $intent=>$words) {


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

                'type'=>'general',

                'confidence'=>0.5

            ];


        }







        return [

            'type'=>
                $matched[0],


            'all'=>
                array_values(
                    array_unique(
                        $matched
                    )
                ),


            'confidence'=>0.8

        ];



    }


}
