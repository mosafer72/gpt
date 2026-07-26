<?php

namespace App\Application\Services\Documents;


use Illuminate\Support\Facades\Log;



class DocumentContextBuilder
{


    public function build(

        array $chunks,

        int $maxWords = 3000

    ): array
    {


        if (empty($chunks)) {

            return [];

        }






        $chunks = collect($chunks)

            ->filter(

                fn($item) =>

                    is_array($item)

                    &&

                    !empty(
                        trim(
                            $item['content'] ?? ''
                        )
                    )

            )

            ->map(

                function ($item) {


                    $item['metadata'] =

                        $item['metadata']
                        ??
                        [];



                    $item['title'] =

                        $item['metadata']['title']
                        ??
                        'Unknown Document';




                    $item['section'] =

                        strtolower(

                            trim(

                                $item['metadata']['section']
                                ??
                                'general'

                            )

                        );




                    $item['score'] =

                        (float)

                        (

                            $item['final_score']
                            ??

                            $item['score']
                            ??

                            0

                        );



                    return $item;


                }

            )

            ->unique(

                fn($item) =>

                    md5(
                        trim(
                            $item['content']
                        )
                    )

            )

            ->sortByDesc(

                fn($item) =>

                    $item['score']

            );








        $documentInfo = [

            'title' =>

                $chunks
                    ->first()['title']
                    ??
                    null,


            'documents' =>

                $chunks

                    ->pluck('title')

                    ->unique()

                    ->values()

                    ->all()

        ];








        $grouped =

            $chunks

                ->groupBy(
                    'section'
                );







        $context = [];

        $wordCount = 0;






        foreach ($grouped as $section => $items) {


            $sectionText = '';



            foreach ($items as $chunk) {


                $content =

                    trim(
                        $chunk['content']
                    );



                $words =

                    str_word_count(
                        $content
                    );



                if (

                    ($wordCount + $words)

                    >

                    $maxWords

                ) {

                    break 2;

                }





                $sectionText .=

                    $content
                    .
                    "\n\n";



                $wordCount += $words;


            }






            if (blank($sectionText)) {

                continue;

            }





            $context[] = [


                'title' =>

                    $items
                        ->first()['title']
                        ??
                        null,



                'section' =>

                    $section,



                'content' =>

                    trim(
                        $sectionText
                    ),



                'chunk_count' =>

                    count($items),



                'importance' =>

                    $this->sectionImportance(
                        $section
                    )


            ];



        }







        Log::info(

            'DOCUMENT CONTEXT BUILT',

            [

                'title' =>
                    $documentInfo['title'],

                'sections' =>
                    count($context),

                'words' =>
                    $wordCount

            ]

        );






        return [

            'document_info' =>
                $documentInfo,


            'sections' =>
                $context


        ];


    }
        public function toText(
        array $context
    ): string
    {


        if (empty($context)) {

            return '';

        }





        $text =

            "Retrieved Document Context:\n\n";







        if (

            isset(
                $context['document_info']
            )

        ) {


            $info =
                $context['document_info'];



            $text .=

                "Document Information:\n\n";



            if (
                !empty(
                    $info['title']
                )
            ) {


                $text .=

                    "Title: "

                    .

                    $info['title']

                    .

                    "\n\n";


            }



        }








        $sections =

            $context['sections']
            ??
            [];







        foreach ($sections as $index => $block) {


            $number =

                $index + 1;




            $text .=

                "====================\n"

                .

                "Section {$number}: "

                .

                ucfirst(

                    $block['section']

                )

                .

                "\n";






            if (

                !empty(
                    $block['title']
                )

            ) {


                $text .=

                    "Document: "

                    .

                    $block['title']

                    .

                    "\n";


            }






            $text .=

                "Importance: "

                .

                $block['importance']

                .

                "\n\n"

                .

                $block['content']

                .

                "\n\n";



        }







        return trim($text);


    }









    protected function sectionImportance(
        string $section
    ): string
    {


        return match ($section) {


            'method',

            'methodology',

            'architecture',

            'model',

            'implementation'

            =>

            'very_high',






            'results',

            'experiment',

            'evaluation',

            'conclusion'

            =>

            'high',






            'abstract',

            'introduction'

            =>

            'medium',






            'references',

            'bibliography'

            =>

            'low',






            default

            =>

            'normal'


        };


    }



}
