<?php

namespace App\Application\Services\Documents;


use App\Application\Services\Ai\EmbeddingService;
use App\Domain\Contracts\SmallChatProvider;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Infrastructure\Repositories\DocumentSectionTranslationRepository;



class SectionTranslationService
{


    public function __construct(

        protected SmallChatProvider $llm,

        protected EmbeddingService $embedding,

        protected DocumentSectionTranslationRepository $repository

    ) {
    }







    public function translate(

        array $section,

        string $documentId,

        string $workspaceId

    ): void {


        $content =
            $section['content']
            ??
            '';



        if (blank($content)) {

            return;

        }





        $prompt = <<<PROMPT

You are an academic translator.

Translate the following scientific paper section into Persian.

Rules:

- Preserve technical terms.
- Keep formulas unchanged.
- Write academic Persian.
- Also provide a short Persian summary.
- Return ONLY valid JSON.
- Do not use markdown.
- Do not add explanations.

Return JSON:

{
"title":"",
"translation":"",
"summary":"",
"keywords":[]
}


Section:

$content

PROMPT;







        /*
        |--------------------------------------------------------------------------
        | Small LLM Translation
        |--------------------------------------------------------------------------
        */


        $response =

            $this->llm->chat(

                new ChatRequest(

                    messages: [

                        new ChatMessage(

                            role: 'user',

                            content: $prompt

                        )

                    ],


                    model:

                    'qwen2.5-64k:latest',


                    temperature:

                    0.2,


                    maxTokens:

                    2500

                )

            );





        $result =

            $response->content;






        /*
        |--------------------------------------------------------------------------
        | Clean JSON Response
        |--------------------------------------------------------------------------
        */


        $result = trim($result);



        $result = preg_replace(

            '/^```json|```$/',

            '',

            $result

        );



        $json =

            json_decode(

                trim($result),

                true

            );





        if (!is_array($json)) {


            \Log::warning(

                'SECTION TRANSLATION INVALID JSON',

                [

                    'document_id' =>
                        $documentId,


                    'response' =>
                        $result

                ]

            );


            return;

        }








        /*
        |--------------------------------------------------------------------------
        | Translation Embedding
        |--------------------------------------------------------------------------
        */


        $embedding =

            $this->embedding

                ->embedDocument(

                    $json['translation']
                    ??
                    ''

                );








        /*
        |--------------------------------------------------------------------------
        | Store Translation
        |--------------------------------------------------------------------------
        */


        $this->repository

            ->store([


                'document_id' =>

                    (string)
                    $documentId,



                'workspace_id' =>

                    (string)
                    $workspaceId,



                'section' =>

                    $section['section']
                    ??
                    'unknown',




                'title' =>

                    $json['title']
                    ??
                    '',




                'original_content' =>

                    $content,





                'translated_content' =>

                    $json['translation']
                    ??
                    '',




                'summary_fa' =>

                    $json['summary']
                    ??
                    '',




                'keywords' =>

                    $json['keywords']
                    ??
                    [],




                'embedding' =>

                    $embedding,





                'metadata' => [

                    'order' =>

                        $section['order']
                        ??
                        0,



                    'word_count' =>

                        $section['word_count']
                        ??
                        0,



                    'translated_by' =>

                        'qwen2.5-64k:latest'

                ]


            ]);






        \Log::info(

            'SECTION TRANSLATED SUCCESSFULLY',

            [

                'document_id' =>
                    $documentId,


                'section' =>
                    $section['section']
                    ??
                    'unknown'

            ]

        );


    }


}
