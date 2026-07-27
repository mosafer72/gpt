<?php

namespace App\Application\Services\Documents;


class DocumentSectionExtractor
{


    public function extract(
        string $text
    ): array {


        $lines =
            preg_split(
                "/\r\n|\n/",
                $text
            );


        $sections = [];


        $currentTitle = 'Document Header';

        $buffer = [];



        foreach ($lines as $line) {


            $line =
                trim($line);



            if (blank($line)) {

                continue;

            }



            if (
                $this->isHeading($line)
            ) {


                if (!empty($buffer)) {


                    $sections[] = [

                        'title' =>
                            $currentTitle,


                        'text' =>
                            trim(
                                implode(
                                    "\n",
                                    $buffer
                                )
                            )

                    ];


                }



                $currentTitle = $line;


                $buffer = [];


                continue;

            }



            $buffer[] = $line;


        }



        if (!empty($buffer)) {


            $sections[] = [

                'title' =>
                    $currentTitle,


                'text' =>
                    trim(
                        implode(
                            "\n",
                            $buffer
                        )
                    )

            ];


        }




        return $sections;


    }






    protected function isHeading(
        string $line
    ): bool {



        /*
        احتمال heading بودن:
        1- کوتاه باشد
        2- جمله کامل نباشد
        3- با عدد شروع شود
        4- کلمات رایج section
        */


        if (
            mb_strlen($line) > 80
        ) {

            return false;

        }



        if (
            preg_match(
                '/^[0-9IVX]+\./',
                $line
            )
        ) {

            return true;

        }



        $known = [

            'abstract',

            'introduction',

            'background',

            'method',

            'methodology',

            'approach',

            'experiment',

            'evaluation',

            'results',

            'discussion',

            'conclusion',

            'references'

        ];



        return in_array(

            strtolower($line),

            $known,

            true

        );


    }


}
