<?php

namespace App\Application\Services\Documents;


class DocumentPromptContextBuilder
{


    public function build(

        array $context,

        string $question

    ): array {



        return [

            'question' =>
                $question,


            'document_context' =>
                $context['text']
                ??
                '',



            'metadata' => [

                'documents' =>

                    $context['document_info']['documents']
                    ??
                    [],



                'sections' =>

                    count(
                        $context['sections']
                        ??
                        []
                    ),



                'words' =>

                    $context['total_words']
                    ??
                    0

            ]

        ];


    }








    public function toPrompt(

        array $context

    ): string {



        $prompt =

<<<TEXT
You are a document based AI assistant.

Answer the user question ONLY using the provided document context.

Rules:
- Do not use outside knowledge.
- Do not invent information.
- If the answer is not available in the document, say that it is not found.

Document Context:

TEXT;



        $prompt .=

            $context['document_context']
            ??
            '';





        $prompt .=



<<<TEXT


User Question:

TEXT;



        $prompt .=

            $context['question']
            ??
            '';





        return trim($prompt);


    }


}
