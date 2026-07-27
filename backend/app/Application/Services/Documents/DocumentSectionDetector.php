<?php

namespace App\Application\Services\Documents;


class DocumentSectionDetector
{


    protected array $knownSections = [

        'abstract',
        'introduction',
        'background',
        'related work',
        'literature review',
        'preliminaries',

        'method',
        'methods',
        'methodology',
        'approach',
        'framework',
        'architecture',
        'model',
        'implementation',

        'dataset',

        'experiment',
        'experiments',
        'experimental setup',

        'evaluation',

        'results',
        'discussion',

        'conclusion',
        'future work',
        'limitations',

        'references',
        'bibliography',

        'appendix',

    ];









    public function __invoke(
        string $text
    ): array {


        return $this->detect($text);

    }









    public function detect(
        string $text
    ): array {


        $text =
            trim(
                str_replace(
                    "\r",
                    "",
                    $text
                )
            );



        if (blank($text)) {

            return [];

        }




        $lines =
            explode(
                "\n",
                $text
            );




        $headings = [];





        foreach ($lines as $index => $line) {


            $line = trim($line);



            if ($line === '') {

                continue;

            }




            $heading =
                $this->detectHeading(
                    $line
                );




            if ($heading) {


                $headings[] = [

                    'line' =>
                        $index,


                    'title' =>
                        $heading['title'],


                    'normalized' =>
                        $this->normalize(
                            $heading['title']
                        ),


                    'level' =>
                        $heading['level'],


                    'type' =>
                        $heading['type'],


                    'confidence' =>
                        $heading['confidence']

                ];


            }


        }





        return $this->buildSections(

            $lines,

            $headings

        );


    }









    protected function buildSections(

        array $lines,

        array $headings

    ): array {


        if (empty($headings)) {


            return [

                [

                    'title' =>
                        'Document',


                    'normalized' =>
                        'general',


                    'level' =>
                        1,


                    'detection_type' =>
                        'fallback',


                    'confidence' =>
                        0.3,


                    'heading_path' =>
                        [],


                    'content' =>
                        implode(
                            "\n",
                            $lines
                        )

                ]

            ];


        }





        $sections = [];

        $stack = [];







        foreach ($headings as $index => $heading) {



            $start =
                $heading['line'] + 1;



            $end =
                $headings[$index + 1]['line']
                ??
                count($lines);





            $content =
                trim(

                    implode(

                        "\n",

                        array_slice(

                            $lines,

                            $start,

                            $end - $start

                        )

                    )

                );





            if ($content === '') {

                continue;

            }






            while (

                count($stack)

                >=

                $heading['level']

            ) {


                array_pop($stack);


            }





            $stack[] =
                $heading['title'];







            $sections[] = [

                'title' =>
                    $heading['title'],


                'normalized' =>
                    $heading['normalized'],


                'level' =>
                    $heading['level'],


                'detection_type' =>
                    $heading['type'],


                'confidence' =>
                    $heading['confidence'],


                'heading_path' =>
                    $stack,


                'content' =>
                    $content,


                'start_line' =>
                    $start,


                'end_line' =>
                    $end


            ];


        }




        return $sections;


    }









    protected function detectHeading(
        string $line
    ): ?array {



        if (
            mb_strlen($line) > 120
        ) {

            return null;

        }







        /*
        |--------------------------------------------------------------------------
        | Numbered heading
        |--------------------------------------------------------------------------
        */


        if (

            preg_match(

                '/^(\d+(\.\d+)*)[\.\)]?\s+(.+)$/',

                $line,

                $matches

            )

        ) {



            $number =
                $matches[1];



            $title =
                trim(
                    $matches[3]
                );



            return [

                'title' =>
                    $title,


                'level' =>
                    substr_count(
                        $number,
                        '.'
                    ) + 1,


                'type' =>
                    'numbered',


                'confidence' =>
                    0.95

            ];


        }








        /*
        |--------------------------------------------------------------------------
        | Uppercase heading
        |--------------------------------------------------------------------------
        */


        if (

            mb_strlen($line) < 80

            &&

            strtoupper($line)===$line

            &&

            preg_match(
                '/[A-Z]/',
                $line
            )

        ) {


            return [

                'title' =>
                    $line,


                'level'=>1,


                'type'=>
                    'uppercase',


                'confidence'=>
                    0.85

            ];


        }









        /*
        |--------------------------------------------------------------------------
        | Academic heading
        |--------------------------------------------------------------------------
        */


        $normalized =
            $this->normalize($line);




        foreach ($this->knownSections as $section) {



            if (

                str_contains(

                    $normalized,

                    $this->normalize(
                        $section
                    )

                )

            ) {


                return [

                    'title'=>
                        $line,


                    'level'=>1,


                    'type'=>
                        'academic',


                    'confidence'=>
                        0.90

                ];


            }


        }









        /*
        |--------------------------------------------------------------------------
        | Short heading
        |--------------------------------------------------------------------------
        */


        if (

            mb_strlen($line)<60

            &&

            str_word_count($line)<=6

        ) {


            return [

                'title'=>
                    $line,


                'level'=>1,


                'type'=>
                    'heuristic',


                'confidence'=>
                    0.55

            ];


        }







        return null;


    }









    protected function normalize(
        string $value
    ): string {


        return strtolower(

            trim(

                preg_replace(

                    '/[^a-zA-Z0-9\s]/',

                    '',

                    $value

                )

            )

        );


    }



}
