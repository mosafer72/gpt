<?php

namespace App\Application\Services\Documents;


class Chunker
{


    public function __construct(
        protected DocumentSectionDetector $detector
    ) {
    }






    /**
     * Main document splitter
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







        /*
        |--------------------------------------------------------------------------
        | Detect real document structure
        |--------------------------------------------------------------------------
        */


        $sections =
            $this->detector
                ->detect(
                    $text
                );






        if (empty($sections)) {


            return [];


        }







        $chunks = [];

        $index = 1;







        foreach ($sections as $section) {





            $content =
                trim(
                    $section['content']
                    ??
                    ''
                );





            if ($content === '') {

                continue;

            }






            $normalized =

                strtolower(

                    trim(

                        $section['normalized']
                        ??
                        'general'

                    )

                );






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

                    $normalized,



                'section_title' =>

                    $section['title']
                    ??
                    ucfirst($normalized),




                'heading_path' =>

                    [

                        $section['title']
                        ??
                        ucfirst($normalized)

                    ],






                'section_level' =>

                    $section['level']
                    ??
                    1,



                'start_line' =>

                    $section['start_line']
                    ??
                    null,



                'end_line' =>

                    $section['end_line']
                    ??
                    null,







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
                        $normalized
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
                | Content
                |--------------------------------------------------------------------------
                */


                'content' =>

                    "# "

                    .

                    (
                        $section['title']
                        ??
                        ''
                    )

                    .

                    "\n\n"

                    .

                    $content



            ];





            $index++;


        }







        return $chunks;


    }









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

            'approach' =>

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

            'conclusion' =>

                'high',






            'header' =>

                'medium',






            'references',

            'bibliography',

            'acknowledgement',

            'acknowledgments',

            'appendix' =>

                'low',






            default =>

                'medium'


        };

    }



}
