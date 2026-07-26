<?php

namespace App\Application\Services\Documents;


use Illuminate\Support\Facades\Log;



class AnswerEvidenceBuilder
{


    /**
     * Build evidence map from retrieved context
     */
    public function build(

        array $context

    ): array {


        if (empty($context)) {

            return [];

        }



        $evidence = [];



        $index = 1;




        foreach ($context as $section) {



            if (
                empty($section['content'])
            ) {

                continue;

            }





            $content =
                trim(
                    $section['content']
                );



            $evidence[] = [


                'id' =>
                    $index,



                'section' =>

                    $section['section']
                    ??
                    'unknown',



                'importance' =>

                    $section['importance']
                    ??
                    'normal',



                'content' =>

                    $content,



                'word_count' =>

                    str_word_count(
                        $content
                    ),



                'citation' =>

                    "[{$index}]"

            ];



            $index++;



        }






        Log::info(

            'ANSWER EVIDENCE BUILT',

            [

                'evidence_count' =>
                    count($evidence)

            ]

        );



        return $evidence;


    }









    /**
     * Convert evidence to prompt text
     */
    public function toPromptText(

        array $evidence

    ): string {



        if (empty($evidence)) {

            return '';

        }





        $text =

            "Evidence Sources:\n\n";





        foreach ($evidence as $item) {



            $text .=

                "Source {$item['citation']}\n"

                .

                "Section: "

                .

                ucfirst(
                    $item['section']
                )

                .

                "\n\n"

                .

                $item['content']

                .

                "\n\n"

                .

                "----------------------\n\n";



        }





        return trim($text);


    }









    /**
     * Attach citation ids to context
     */
    public function attachCitations(

        array $answer,

        array $evidence

    ): array {



        foreach ($evidence as $item) {


            $answer[] = [

                'citation' =>
                    $item['citation'],

                'section' =>
                    $item['section']

            ];


        }



        return $answer;


    }



}
