<?php

namespace App\Application\Services\Documents;


class SectionPlanner
{


    public function plan(
        string $intent
    ): array {



        $plans = [


            'title'=>[

                'title'=>100,

                'abstract'=>80,

                'introduction'=>40

            ],



            'abstract'=>[

                'abstract'=>100,

                'introduction'=>50,

                'conclusion'=>50

            ],




            'methodology'=>[

                'methodology'=>100,

                'method'=>100,

                'approach'=>90,

                'architecture'=>90,

                'implementation'=>80

            ],





            'dataset'=>[

                'dataset'=>100,

                'experiment'=>90,

                'methodology'=>70

            ],





            'experiment'=>[

                'experiment'=>100,

                'setup'=>90,

                'evaluation'=>80

            ],




            'result'=>[

                'results'=>100,

                'evaluation'=>90,

                'experiment'=>70

            ],




            'conclusion'=>[

                'conclusion'=>100,

                'future work'=>80,

                'discussion'=>70

            ],




            'general'=>[

                'abstract'=>80,

                'introduction'=>70,

                'conclusion'=>60,

                'methodology'=>50

            ]

        ];





        return

            $plans[$intent]
            ??
            $plans['general'];


    }



}
