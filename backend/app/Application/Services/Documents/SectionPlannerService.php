<?php

namespace App\Application\Services\Documents;

use App\Application\Services\Ai\AiCompletionService;
use App\Domain\Contracts\SectionPlanner;
use App\Infrastructure\Repositories\DocumentSectionPlanRepository;
use Illuminate\Support\Facades\Log;


class SectionPlannerService implements SectionPlanner
{


    public function __construct(

        protected AiCompletionService $ai,

        protected DocumentSectionPlanRepository $repository

    ) {
    }



    public function analyze(

        string $documentId,

        string $workspaceId,

        array $sections

    ): array {


        if (empty($sections)) {

            return [];

        }



        $plans = [];




        foreach ($sections as $section) {


            try {


                /*
                |--------------------------------------------------------------------------
                | Normalize Chunk Input
                |--------------------------------------------------------------------------
                */


                $rawSection =
                    $section['section'] ?? '';



                if (is_array($rawSection)) {

                    $sectionName =
                        implode(
                            ' ',
                            $rawSection
                        );

                } else {

                    $sectionName =
                        (string)
                        $rawSection;

                }




                $rawTitle =
                    $section['section_title'] ?? '';



                if (
                    is_array($rawTitle)
                    ||
                    is_object($rawTitle)
                ) {


                    $sectionTitle =
                        json_encode(
                            $rawTitle,
                            JSON_UNESCAPED_UNICODE
                        );


                } else {


                    $sectionTitle =
                        (string)
                        $rawTitle;

                }




                $rawContent =
                    $section['content'] ?? '';



                if (
                    is_array($rawContent)
                    ||
                    is_object($rawContent)
                ) {


                    $rawContent =
                        json_encode(
                            $rawContent,
                            JSON_UNESCAPED_UNICODE
                        );


                } else {


                    $rawContent =
                        (string)
                        $rawContent;

                }




                $content =
                    mb_substr(
                        $rawContent,
                        0,
                        1500
                    );





                $prompt = <<<PROMPT
You are an academic paper analysis assistant.

Analyze this research paper section.

Return ONLY valid JSON.

Format:

{
"answer_probability":0.0,
"keywords":[],
"question_types":[],
"summary":""
}

Rules:

- answer_probability must be between 0 and 1.
- Higher means more useful for answering questions.
- Methodology, Architecture, Experiments, Results and Conclusion are usually important.

Section:
{$sectionName}

Title:
{$sectionTitle}

Content:
{$content}

PROMPT;




                $response =
                    $this->ai->complete(
                        $prompt,
                        config('services.planner.model')
                    );





                $jsonText =
                    trim($response);



                $jsonText =
                    preg_replace(
                        '/```(?:json)?|```/i',
                        '',
                        $jsonText
                    );



                $jsonText =
                    trim($jsonText);




                if (
                    preg_match(
                        '/\{.*\}/s',
                        $jsonText,
                        $matches
                    )
                ) {


                    $jsonText =
                        $matches[0];

                }





                $json =
                    json_decode(
                        $jsonText,
                        true
                    );



                if (!is_array($json)) {


                    Log::warning(
                        'Invalid planner JSON',
                        [
                            'section' => $sectionName,
                            'response' => $response
                        ]
                    );


                    continue;

                }






                $probability =
                    (float)
                    ($json['answer_probability'] ?? 0);



                if ($probability > 1) {

                    $probability /= 100;

                }



                $probability =
                    min(
                        max(
                            $probability,
                            0
                        ),
                        1
                    );





                $summary =
                    $json['summary'] ?? '';



                if (
                    is_array($summary)
                    ||
                    is_object($summary)
                ) {


                    $summary =
                        json_encode(
                            $summary,
                            JSON_UNESCAPED_UNICODE
                        );


                } else {


                    $summary =
                        (string)
                        $summary;

                }





                $plans[] = [


                    'document_id' =>
                        (string) $documentId,


                    'workspace_id' =>
                        (string) $workspaceId,


                    'section' =>
                        strtolower(
                            trim($sectionName)
                        ),



                    'section_title' => trim(
                        str_replace(
                            ':',
                            ' ',
                            $sectionTitle
                        )
                    ),



                    'summary' => trim(
                        str_replace(
                            ':',
                            ' ',
                            $summary
                        )
                    ),


                    'keywords' =>
                        collect(
                            $json['keywords'] ?? []
                        )
                            ->map(
                                fn($x) => (string) $x
                            )
                            ->values()
                            ->toArray(),




                    'question_types' =>
                        collect(
                            $json['question_types'] ?? []
                        )
                            ->map(
                                fn($x) => (string) $x
                            )
                            ->values()
                            ->toArray(),




                    'answer_probability' =>
                        $probability,



                    'planner_rank' => 0,


                    'selected_count' => 0,



                    'metadata' => [

                        'word_count' =>
                            (int)
                            ($section['word_count'] ?? 0),

                        'chunk_count' =>
                            (int)
                            ($section['chunk_count'] ?? 0),

                    ],



                    'created_at' =>
                        now()->toISOString(),

                ];



            } catch (\Throwable $e) {


                Log::warning(
                    'Section planner failed',
                    [
                        'error' => $e->getMessage()
                    ]
                );


            }


        }





        usort(
            $plans,
            fn($a, $b) =>
            $b['answer_probability']
            <=>
            $a['answer_probability']
        );





        foreach ($plans as $i => $plan) {


            $plans[$i]['planner_rank'] =
                $i + 1;


        }






        if (!empty($plans)) {


            $this->repository
                ->storeBulk(
                    $plans
                );


        }




        return $plans;


    }


}
