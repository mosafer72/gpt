<?php

namespace App\Application\Services\Documents;


use App\Infrastructure\Repositories\DocumentStructureRepository;
use App\Models\Document;



class DocumentStructureBuilder
{


    public function __construct(
        protected DocumentStructureRepository $repository
    ) {
    }







    /**
     * Build document structure
     *
     * Input:
     *  - Sections from DocumentSectionDetector
     *
     * Output:
     *  - Document structure metadata
     */
    public function build(

        Document $document,

        array $sections

    ): array {


        if (empty($sections)) {

            return [];

        }







        $structureSections = [];

        $order = 1;








        foreach ($sections as $section) {



            $title =

                trim(

                    $section['title']
                    ??
                    'Untitled Section'

                );






            /**
             * Compatible with DocumentSectionDetector
             */
            $text =

                trim(

                    $section['content']
                    ??
                    ''

                );







            if ($text === '') {

                continue;

            }








            $name =

                $this->normalizeSection(
                    $title
                );








            $structureSections[] = [


                /*
                |--------------------------------------------------------------------------
                | Identity
                |--------------------------------------------------------------------------
                */


                'name' =>

                    $name,



                'normalized' =>

                    $name,



                'title' =>

                    $title,



                'order' =>

                    $order++,







                /*
                |--------------------------------------------------------------------------
                | Content Information
                |--------------------------------------------------------------------------
                */


                'summary' =>

                    mb_substr(

                        $text,

                        0,

                        700

                    ),




                'word_count' =>

                    $this->wordCount(
                        $text
                    ),







                /*
                |--------------------------------------------------------------------------
                | Retrieval Metadata
                |--------------------------------------------------------------------------
                */


                'importance' =>

                    $this->detectImportance(
                        $title
                    ),




                'keywords' =>

                    $this->extractKeywords(
                        $text
                    ),







                /*
                |--------------------------------------------------------------------------
                | Translation Pipeline
                |--------------------------------------------------------------------------
                */


                'translation' => [

                    'status' =>
                        'pending',


                    'title_fa' =>
                        null,


                    'summary_fa' =>
                        null,

                ],




                /*
                |--------------------------------------------------------------------------
                | Source Position
                |--------------------------------------------------------------------------
                */


                'start_line' =>

                    $section['start_line']
                    ??
                    null,


                'end_line' =>

                    $section['end_line']
                    ??
                    null,


            ];



        }







        $payload = [





            /*
            |--------------------------------------------------------------------------
            | Document Identity
            |--------------------------------------------------------------------------
            */


            'document_id' =>

                (string)
                $document->id,



            'workspace_id' =>

                (string)
                $document->workspace_id,








            /*
            |--------------------------------------------------------------------------
            | Document Metadata
            |--------------------------------------------------------------------------
            */


            'title' => [


                'name' =>

                    $document->name,


                'type' =>

                    'academic_paper'


            ],







            /*
            |--------------------------------------------------------------------------
            | Real Sections
            |--------------------------------------------------------------------------
            */


            'sections' =>

                $structureSections,









            /*
            |--------------------------------------------------------------------------
            | Statistics
            |--------------------------------------------------------------------------
            */


            'statistics' => [


                'section_count' =>

                    count(
                        $structureSections
                    ),



                'total_words' =>

                    array_sum(

                        array_column(

                            $structureSections,

                            'word_count'

                        )

                    )


            ],








            'created_at' =>

                now()
                    ->toISOString()



        ];








        $this->repository
            ->store(
                $payload
            );








        return $payload;


    }









    /**
     * Normalize section name
     */
    protected function normalizeSection(

        string $title

    ): string {


        return mb_strtolower(

            trim(

                preg_replace(

                    '/[^\p{L}\p{N}\s]/u',

                    '',

                    $title

                )

            )

        );


    }









    /**
     * Count words
     */
    protected function wordCount(

        string $text

    ): int {


        $text =

            trim($text);




        if ($text === '') {

            return 0;

        }






        return count(

            preg_split(

                '/\s+/u',

                $text

            )

        );


    }









    /**
     * Detect section importance
     */
    protected function detectImportance(

        string $title

    ): string {


        $title =

            mb_strtolower(
                $title
            );







        if (

            str_contains(
                $title,
                'abstract'
            )

            ||

            str_contains(
                $title,
                'چکیده'
            )

            ||

            str_contains(
                $title,
                'introduction'
            )

            ||

            str_contains(
                $title,
                'مقدمه'
            )

        ) {

            return 'high';

        }








        if (

            str_contains(
                $title,
                'method'
            )

            ||

            str_contains(
                $title,
                'methodology'
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

            ||

            str_contains(
                $title,
                'experiment'
            )

            ||

            str_contains(
                $title,
                'evaluation'
            )

            ||

            str_contains(
                $title,
                'روش'
            )

            ||

            str_contains(
                $title,
                'آزمایش'
            )

        ) {

            return 'very_high';

        }








        if (

            str_contains(
                $title,
                'reference'
            )

            ||

            str_contains(
                $title,
                'bibliography'
            )

            ||

            str_contains(
                $title,
                'appendix'
            )

            ||

            str_contains(
                $title,
                'منابع'
            )

        ) {

            return 'low';

        }








        return 'medium';


    }









    /**
     * Lightweight keyword extraction
     */
    protected function extractKeywords(

        string $text

    ): array {


        $words =

            preg_split(

                '/\s+/u',

                mb_strtolower($text)

            );





        return collect($words)

            ->map(

                fn($word) =>

                    preg_replace(

                        '/[^\p{L}\p{N}]/u',

                        '',

                        $word

                    )

            )

            ->filter(

                fn($word) =>

                    mb_strlen($word) > 5

            )

            ->countBy()

            ->sortDesc()

            ->take(10)

            ->keys()

            ->values()

            ->all();


    }



}
