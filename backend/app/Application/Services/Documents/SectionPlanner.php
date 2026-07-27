<?php

namespace App\Application\Services\Documents;


class SectionPlanner
{


    public function plan(

        array $intent,

        array $sections = [],

        string $query = ''

    ): array {


        $type =

            strtolower(

                trim(

                    $intent['type']
                    ??
                    'general'

                )

            );






        /*
        |--------------------------------------------------------------------------
        | No Structure Available
        |--------------------------------------------------------------------------
        */


        if(empty($sections)){


            return [

                'mode'=>'global',


                'sections'=>[]


            ];


        }







        $scores = [];






        foreach($sections as $section){



            $title =

                strtolower(

                    trim(

                        $section['name']
                        ??

                        $section['title']
                        ??

                        ''

                    )

                );





            if($title === ''){

                continue;

            }






            $score = 0;







            /*
            |--------------------------------------------------------------------------
            | Intent Based Scoring
            |--------------------------------------------------------------------------
            */


            switch($type){



                case 'title':


                    if(
                        str_contains(
                            $title,
                            'header'
                        )
                        ||
                        str_contains(
                            $title,
                            'abstract'
                        )
                    ){

                        $score = 100;

                    }


                    break;







                case 'summary':


                    if(
                        in_array(

                            $title,

                            [

                                'abstract',

                                'introduction',

                                'conclusion'

                            ],

                            true

                        )
                    ){

                        $score = 90;

                    }


                    break;







                case 'section_specific':


                    $score =
                        $this->sectionMatchScore(
                            $query,
                            $title
                        );


                    break;







                case 'comparison':


                    if(

                        str_contains(
                            $title,
                            'result'
                        )

                        ||

                        str_contains(
                            $title,
                            'evaluation'
                        )

                    ){

                        $score = 90;

                    }


                    break;








                case 'explanation':


                    if(

                        str_contains(
                            $title,
                            'method'
                        )

                        ||

                        str_contains(
                            $title,
                            'model'
                        )

                        ||

                        str_contains(
                            $title,
                            'architecture'
                        )

                    ){

                        $score = 85;

                    }


                    break;







                default:


                    $score =
                        $this->generalScore(
                            $title
                        );


                    break;


            }






            if($score > 0){


                $scores[$title] = $score;


            }



        }







        /*
        |--------------------------------------------------------------------------
        | Sort Sections
        |--------------------------------------------------------------------------
        */


        arsort($scores);







        /*
        |--------------------------------------------------------------------------
        | Decide Search Mode
        |--------------------------------------------------------------------------
        */


        if(empty($scores)){


            return [

                'mode'=>'global',

                'sections'=>[]

            ];


        }







        return [

            'mode'=>'section',

            'sections'=>$scores


        ];



    }









    protected function sectionMatchScore(

        string $query,

        string $section

    ): int {



        $query =
            strtolower($query);





        $score = 0;





        if(
            str_contains(
                $query,
                $section
            )
        ){

            $score +=100;

        }






        $keywords = [


            'method'=>[

                'method',

                'approach',

                'architecture',

                'model'

            ],



            'dataset'=>[

                'data',

                'dataset'

            ],



            'experiment'=>[

                'experiment',

                'setup'

            ],



            'result'=>[

                'result',

                'evaluation',

                'performance'

            ],



            'conclusion'=>[

                'conclusion',

                'future'

            ]


        ];







        foreach($keywords as $key=>$items){


            foreach($items as $item){


                if(
                    str_contains(
                        $query,
                        $item
                    )

                    &&

                    str_contains(
                        $section,
                        $key
                    )

                ){

                    $score +=50;

                }


            }


        }






        return min(
            $score,
            100
        );


    }









    protected function generalScore(

        string $section

    ): int {



        return match(true){



            str_contains(
                $section,
                'abstract'
            )

            =>

            80,





            str_contains(
                $section,
                'introduction'
            )

            =>

            70,





            str_contains(
                $section,
                'conclusion'
            )

            =>

            60,





            str_contains(
                $section,
                'method'
            )

            =>

            50,





            default

            =>

            30


        };


    }



}
