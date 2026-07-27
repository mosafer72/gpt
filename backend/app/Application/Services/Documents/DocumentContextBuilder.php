<?php

namespace App\Application\Services\Documents;


use App\Application\DTOs\Documents\RetrievalResult;

use Illuminate\Support\Facades\Log;



class DocumentContextBuilder
{


    public function build(

        array $results,

        int $maxWords = 3000

    ): array {


        if(empty($results)){

            return [];

        }





        $chunks = collect($results)

            ->map(function($item){



                if($item instanceof RetrievalResult){


                    if(method_exists($item,'toArray')){


                        return $item->toArray();


                    }



                    return [

                        'content' =>
                            $item->content
                            ??
                            '',


                        'score' =>
                            $item->score
                            ??
                            0,


                        'metadata' =>
                            $item->metadata
                            ??
                            []


                    ];


                }



                return $item;


            })



            ->filter(function($item){


                return

                    is_array($item)

                    &&

                    !empty(

                        trim(

                            $item['content']
                            ??
                            ''

                        )

                    );


            })



            ->map(function($item){



                $metadata =

                    $item['metadata']
                    ??
                    [];




                $item['metadata'] =
                    $metadata;





                $item['title'] =

                    $metadata['title']
                    ??

                    'Unknown Document';






                $item['section'] =

                    strtolower(

                        trim(

                            $metadata['section']
                            ??

                            'general'

                        )

                    );





                $item['chunk_id'] =

                    $item['chunk_id']
                    ??

                    $metadata['chunk_id']
                    ??

                    null;






                $item['score'] =

                    (float)

                    (

                        $item['final_score']
                        ??

                        $item['planner_score']
                        ??

                        $item['score']
                        ??

                        0

                    );




                return $item;


            })



            ->unique(function($item){



                return


                    $item['chunk_id']


                    ??


                    md5(

                        trim(

                            $item['content']

                        )

                    );


            })



            ->sortByDesc('score')

            ->values();








        if($chunks->isEmpty()){


            return [];

        }








        $context = [];

        $wordCount = 0;








        foreach($chunks as $chunk){



            if($wordCount >= $maxWords){


                break;


            }





            $content =

                trim(

                    $chunk['content']

                );





            $words =

                $this->wordCount(
                    $content
                );








            if(

                ($wordCount + $words)

                >

                $maxWords

            ){



                $content =

                    $this->limitWords(

                        $content,

                        $maxWords - $wordCount

                    );



                $words =

                    $this->wordCount(
                        $content
                    );


            }








            $context[] = [



                'chunk_id' =>

                    $chunk['chunk_id'],



                'title' =>

                    $chunk['title'],



                'section' =>

                    $chunk['section'],



                'content' =>

                    $content,



                'score' =>

                    $chunk['score'],



                'importance' =>

                    $this->sectionImportance(

                        $chunk['section']

                    ),



                'metadata' => [



                    'section_title' =>

                        $chunk['metadata']['section_title']
                        ??
                        '',



                    'chunk_index' =>

                        $chunk['metadata']['chunk_index']
                        ??
                        0,



                    'word_count' =>

                        $words,



                    'original_metadata' =>

                        $chunk['metadata']


                ]


            ];






            $wordCount += $words;



        }








        if(empty($context)){


            return [];

        }







        $result = [



            'document_info' => [



                'title' =>

                    $context[0]['title']
                    ??
                    null,



                'documents' =>

                    collect($context)

                        ->pluck('title')

                        ->unique()

                        ->values()

                        ->all()


            ],




            'sections' =>

                $context,




            'total_words' =>

                $wordCount



        ];








        $result['text'] =

            $this->toText(
                $result
            );








        Log::info(

            'DOCUMENT CONTEXT BUILT',

            [

                'title' =>

                    $result['document_info']['title'],



                'chunks' =>

                    count($context),



                'words' =>

                    $wordCount


            ]

        );








        return $result;


    }









    public function toText(

        array $context

    ): string {



        if(empty($context['sections'])){


            return '';

        }







        $text =

            "Retrieved Document Context:\n\n";








        foreach(

            $context['sections']

            as

            $index=>$section

        ){



            $text .=



                "====================\n"

                .

                "Context Block "

                .

                ($index + 1)

                .

                "\n"



                .

                "Document: "

                .

                ($section['title'] ?? '')

                .

                "\n"



                .

                "Section: "

                .

                ucfirst(

                    $section['section']

                )

                .

                "\n"



                .

                "Importance: "

                .

                $section['importance']

                .

                "\n"



                .

                "Score: "

                .

                round(

                    $section['score'],

                    3

                )

                .

                "\n\n"



                .

                $section['content']

                .

                "\n\n";


        }







        return trim($text);


    }









    protected function sectionImportance(

        string $section

    ): string {



        return match($section){


            'method',
            'methods',
            'methodology',
            'architecture',
            'model',
            'implementation'

            =>

            'very_high',




            'experiment',
            'experiments',
            'evaluation',
            'results',
            'conclusion'

            =>

            'high',




            'abstract',
            'introduction',
            'background'

            =>

            'medium',




            'references',
            'bibliography',
            'appendix'

            =>

            'low',




            default =>

            'normal'


        };


    }









    protected function wordCount(

        string $text

    ): int {


        $text = trim($text);



        if($text === ''){

            return 0;

        }




        return count(

            preg_split(

                '/\s+/u',

                $text

            )

        );


    }









    protected function limitWords(

        string $text,

        int $limit

    ): string {



        $words =

            preg_split(

                '/\s+/u',

                trim($text)

            );




        return implode(

            ' ',

            array_slice(

                $words,

                0,

                max(

                    1,

                    $limit

                )

            )

        );


    }


}
