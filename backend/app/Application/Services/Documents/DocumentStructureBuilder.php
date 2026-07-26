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
    ): array {



        if (empty($sections)) {

            return [];

        }





        /*
        |--------------------------------------------------------------------------
        | Group chunks into real sections
        |--------------------------------------------------------------------------
        */


        $grouped = [];





        foreach ($sections as $section) {



            $name =
                $this->normalizeSection(
                    $section['section']
                    ??
                    ''
                );



            if ($name === '') {

                continue;

            }






            if (!isset($grouped[$name])) {



                $grouped[$name] = [


                    'name' =>
                        $name,



                    'title' =>
                        trim(
                            $section['section_title']
                            ??
                            ucfirst($name)
                        ),



                    'contents' => [],



                    'word_count' => 0,



                    'importance' =>
                        $section['importance']
                        ??
                        'medium',



                    'first_index' =>
                        $section['chunk_index']
                        ??
                        9999,


                ];


            }





            $content =
                trim(
                    $section['content']
                    ??
                    ''
                );




            if ($content !== '') {


                $grouped[$name]['contents'][] =
                    $content;


            }





            $grouped[$name]['word_count']
                +=
                (int)
                (
                    $section['word_count']
                    ??
                    0
                );



        }








        /*
        |--------------------------------------------------------------------------
        | Sort by original document order
        |--------------------------------------------------------------------------
        */


        usort(
            $grouped,
            fn($a, $b) =>
            $a['first_index']
            <=>
            $b['first_index']
        );








        /*
        |--------------------------------------------------------------------------
        | Create Structure
        |--------------------------------------------------------------------------
        */


        $structureSections = [];

        $order = 1;






        foreach ($grouped as $section) {



            $fullText =
                implode(
                    " ",
                    $section['contents']
                );






            $structureSections[] = [



                'name' =>
                    $section['name'],



                'title' =>
                    $section['title'],



                'order' =>
                    $order++,



                'summary' =>
                    mb_substr(
                        $fullText,
                        0,
                        500
                    ),



                'importance' =>
                    $section['importance'],



                'word_count' =>
                    $section['word_count'],




                /*
                |--------------------------------------------------------------------------
                | Translation Pipeline
                |--------------------------------------------------------------------------
                */


                'translation' =>

                    [

                        'status' =>
                            'pending',


                        'title_fa' =>
                            null,


                        'summary_fa' =>
                            null,

                    ]



            ];



        }









        $payload = [



            'document_id' =>
                (string)
                $document->id,



            'workspace_id' =>
                (string)
                $document->workspace_id,



            'title' => [


                'name' =>
                    $document->name,


                'type' =>
                    'academic_paper'


            ],




            'sections' =>
                $structureSections,



            'created_at' =>
                now()->toISOString()


        ];






        $this->repository
            ->store(
                $payload
            );






        return $payload;


    }









    protected function normalizeSection(
        string $section
    ): string {


        return strtolower(

            trim(

                preg_replace(
                    '/[^a-zA-Z\s]/',
                    '',
                    $section
                )

            )

        );

    }



}
