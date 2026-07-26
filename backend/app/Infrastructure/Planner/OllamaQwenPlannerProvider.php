<?php

namespace App\Infrastructure\Planner;

use App\Application\Context\ChatContext;
use App\Application\DTOs\IntentResult;
use App\Domain\Contracts\PlannerProvider;
use Illuminate\Support\Facades\Http;


class OllamaQwenPlannerProvider implements PlannerProvider
{


    protected string $url =
        'http://127.0.0.1:11434/api/generate';



    public function analyze(
        ChatContext $context
    ): IntentResult {



        $prompt = <<<PROMPT
You are a router.

Return ONLY:

true

or

false


true:
- coding
- programming
- research
- paper
- pdf
- document
- analysis
- complex question


false:
- greeting
- casual chat
- simple question


Never explain.
Never use markdown.


User message:

{$context->currentMessage}
PROMPT;




        $start = microtime(true);



        $response = Http::timeout(10)

            ->post(

                $this->url,

                [

                    'model'=>'qwen2.5-64k:latest',

                    'prompt'=>$prompt,


                    'stream'=>false,

                    'think'=>false,


                    'keep_alive'=>'24h',


                    'options'=>[

                        'temperature'=>0,

                        'num_predict'=>3,

                        'top_p'=>0.1,

                        'repeat_penalty'=>1

                    ]

                ]

            );




        \Log::info(
            'PLANNER TIME',
            [
                'seconds'=>
                    microtime(true)-$start
            ]
        );



        if(!$response->successful()){


            throw new \Exception(

                'Planner Ollama Error: '
                .$response->body()

            );

        }




        $data = $response->json();



        $content =
            strtolower(
                trim(
                    $data['response'] ?? ''
                )
            );



        // فقط true معتبر است
        $useMainModel =
            $content === 'true';



        return new IntentResult(

            intent:
                $useMainModel
                    ? 'complex'
                    : 'chat',


            confidence:
                1,


            rewrite:
                $context->currentMessage,


            needsClarification:
                false,


            useMainModel:
                $useMainModel

        );

    }

}
