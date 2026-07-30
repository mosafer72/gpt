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







    public function build(
        Document $document,
        array $sections
    ): array
    {


        if(empty($sections)){

            return [];

        }



        $structureSections=[];
        $order=1;



        foreach($sections as $section){


            $title =
                trim(
                    $section['title'] ?? 'Untitled Section'
                );



            $text =
                trim(
                    $section['content'] ?? ''
                );



            if($text===''){

                continue;

            }



            $normalized =
                $this->normalizeSection(
                    $section['normalized'] ?? $title
                );



            $structureSections[]=[


                'name'=>
                    $normalized,


                'normalized'=>
                    $normalized,


                'title'=>
                    $title,


                'order'=>
                    $order++,


                'summary'=>
                    $this->makeSummary(
                        $text
                    ),



                'word_count'=>
                    $this->wordCount(
                        $text
                    ),



                'importance'=>
                    $this->detectImportance(
                        $normalized
                    ),



                'keywords'=>
                    $this->extractKeywords(
                        $text
                    ),



                'translation'=>[

                    'status'=>'pending',

                    'title_fa'=>null,

                    'summary_fa'=>null

                ],



                'start_line'=>
                    $section['start_line'] ?? 0,


                'end_line'=>
                    $section['end_line'] ?? 0,


            ];

        }







        $payload=[


            'document_id'=>
                (string)$document->id,



            'workspace_id'=>
                (string)$document->workspace_id,



            'title'=>[

                'name'=>
                    $document->name,

                'type'=>
                    'academic_paper'

            ],



            'sections'=>
                $structureSections,



            'statistics'=>[

                'section_count'=>
                    count($structureSections),


                'total_words'=>
                    array_sum(
                        array_column(
                            $structureSections,
                            'word_count'
                        )
                    )

            ],



            'created_at'=>
                now()->toISOString()

        ];






        $this->repository->store(
            $payload
        );



        return $payload;


    }









    protected function normalizeSection(
        string $title
    ):string
    {


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









    protected function makeSummary(
        string $text
    ):string
    {


        $text =
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $text
                )
            );



        if(
            mb_strlen($text)<=700
        ){

            return $text;

        }



        $summary =
            mb_substr(
                $text,
                0,
                700
            );



        $last =
            mb_strrpos(
                $summary,
                '.'
            );



        if($last!==false){

            return mb_substr(
                $summary,
                0,
                $last+1
            );

        }



        return $summary;


    }









    protected function wordCount(
        string $text
    ):int
    {


        $text =
            trim($text);



        if($text===''){

            return 0;

        }



        return count(
            preg_split(
                '/\s+/u',
                $text
            )
        );


    }









    protected function detectImportance(
        string $section
    ):string
    {


        $section =
            mb_strtolower(
                $section
            );



        if(
            in_array(
                $section,
                [
                    'methodology',
                    'experiment',
                    'results',
                    'evaluation'
                ]
            )
        ){

            return 'very_high';

        }





        if(
            in_array(
                $section,
                [
                    'abstract',
                    'introduction',
                    'related_work',
                    'conclusion'
                ]
            )
        ){

            return 'high';

        }





        if(
            in_array(
                $section,
                [
                    'references',
                    'bibliography',
                    'appendix'
                ]
            )
        ){

            return 'low';

        }





        return 'medium';


    }









    protected function extractKeywords(
        string $text
    ):array
    {


        $stopWords=[

            'this',
            'that',
            'with',
            'from',
            'using',
            'based',
            'paper',
            'method',
            'approach',
            'model',
            'data',
            'result',
            'study',
            'also'

        ];



        $words =
            preg_split(
                '/\s+/u',
                mb_strtolower($text)
            );




        return collect($words)

            ->map(
                fn($word)=>
                    preg_replace(
                        '/[^\p{L}\p{N}]/u',
                        '',
                        $word
                    )
            )

            ->filter(
                fn($word)=>
                    mb_strlen($word)>4
                    &&
                    !in_array(
                        $word,
                        $stopWords
                    )
            )

            ->countBy()

            ->sortDesc()

            ->take(10)

            ->keys()

            ->values()

            ->all();


    }


}
