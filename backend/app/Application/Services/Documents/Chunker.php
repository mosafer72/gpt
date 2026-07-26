<?php

namespace App\Application\Services\Documents;


class Chunker
{

    /**
     * Supported academic sections
     */
    protected array $sections = [

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

        'model',

        'architecture',

        'implementation',

        'experiment',

        'experiments',

        'evaluation',

        'results',

        'discussion',

        'conclusion',

        'future work',

        'limitations',

        'references',

        'bibliography',

        'acknowledgement',

        'acknowledgments',

        'appendix',

    ];



    /**
     * Sections that usually have lower retrieval value
     */
    protected array $lowPrioritySections = [

        'references',

        'bibliography',

        'acknowledgement',

        'acknowledgments',

        'appendix',

    ];




    /**
     * Main document splitter
     *
     * One section = one chunk
     */
    public function split(
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




        $sections =
            $this->extractSections(
                $text
            );



        $chunks = [];

        $index = 1;




        foreach ($sections as $section) {


            $content =
                trim(
                    $section['text']
                );



            if ($content === '') {

                continue;

            }



            $wordCount =
                count(
                    preg_split(
                        '/\s+/u',
                        $content
                    )
                );



            $chunks[] = [

                /*
                |--------------------------------------------------------------------------
                | Section Metadata
                |--------------------------------------------------------------------------
                */

                'section' =>
                    $section['normalized_name'],


                'section_title' =>
                    $section['title'],



                'heading_path' =>
                    [
                        $section['title']
                    ],



                /*
                |--------------------------------------------------------------------------
                | Chunk Metadata
                |--------------------------------------------------------------------------
                */


                'chunk_index' =>
                    $index,


                'section_chunk_index' =>
                    1,


                'word_count' =>
                    $wordCount,



                'importance' =>
                    $this->sectionImportance(
                        $section['normalized_name']
                    ),



                /*
                |--------------------------------------------------------------------------
                | Processing Status
                |--------------------------------------------------------------------------
                */


                'translation_status' =>
                    'pending',



                'embedding_status' =>
                    'pending',



                /*
                |--------------------------------------------------------------------------
                | Final Content
                |--------------------------------------------------------------------------
                */

                'content' =>
                    "# {$section['title']}\n\n" .
                    $content,


            ];



            $index++;


        }




        return $chunks;


    }
    /**
     * Extract document sections
     *
     * Everything before first heading
     * will become Header section
     */
    protected function extractSections(
        string $text
    ): array {


        $lines =
            explode(
                "\n",
                $text
            );


        $sections = [];



        $currentName =
            'header';


        $currentTitle =
            'Header';



        $buffer = [];



        $pattern =
            '/^
            (?:
                section\s*
                [\dIVXLCDM]+
                |
                [\d]+
                [\.\)]
            )?
            \s*
            (
                '
            .
            implode(
                '|',
                array_map(
                    fn($item) =>
                    preg_quote(
                        $item,
                        '/'
                    ),
                    $this->sections
                )
            )
            .
            '
            )
            \s*
            (?:
                [:\-]
                .*
            )?
            $
            /ix';




        foreach ($lines as $line) {


            $trim =
                trim($line);



            /*
            |--------------------------------------------------------------------------
            | Empty lines
            |--------------------------------------------------------------------------
            */

            if ($trim === '') {

                $buffer[] = '';

                continue;

            }




            /*
            |--------------------------------------------------------------------------
            | Detect heading
            |--------------------------------------------------------------------------
            */

            if (

                strlen($trim) <= 100

                &&

                preg_match(
                    $pattern,
                    $trim,
                    $matches
                )

            ) {



                /*
                |--------------------------------------------------------------------------
                | Save previous section
                |--------------------------------------------------------------------------
                */


                if (
                    !empty(
                    array_filter(
                        $buffer
                    )
                )
                ) {


                    $sections[] = [

                        'normalized_name' =>
                            strtolower(
                                trim(
                                    $currentName
                                )
                            ),


                        'title' =>
                            $currentTitle,


                        'text' =>
                            implode(
                                "\n",
                                $buffer
                            ),


                    ];


                }




                /*
                |--------------------------------------------------------------------------
                | Start new section
                |--------------------------------------------------------------------------
                */


                $currentName =
                    strtolower(
                        trim(
                            $matches[1]
                        )
                    );



                $currentTitle =
                    $trim;



                $buffer = [];



                continue;


            }




            /*
            |--------------------------------------------------------------------------
            | Normal content
            |--------------------------------------------------------------------------
            */

            $buffer[] = $line;


        }





        /*
        |--------------------------------------------------------------------------
        | Save last section
        |--------------------------------------------------------------------------
        */


        if (
            !empty(
            array_filter(
                $buffer
            )
        )
        ) {


            $sections[] = [

                'normalized_name' =>
                    strtolower(
                        trim(
                            $currentName
                        )
                    ),


                'title' =>
                    $currentTitle,


                'text' =>
                    implode(
                        "\n",
                        $buffer
                    ),


            ];


        }




        return $sections;


    }







    /**
     * Section importance score
     */
    protected function sectionImportance(
        string $section
    ): string {


        return match ($section) {


            'abstract',

            'method',

            'methods',

            'methodology',

            'architecture',

            'model',

            'approach'

            =>

            'very_high',





            'introduction',

            'background',

            'related work',

            'literature review',

            'implementation',

            'experiment',

            'experiments',

            'evaluation',

            'results',

            'discussion',

            'conclusion'

            =>

            'high',





            'header'

            =>

            'medium',





            'references',

            'bibliography',

            'acknowledgement',

            'acknowledgments',

            'appendix'

            =>

            'low',





            default

            =>

            'medium',

        };


    }


}
