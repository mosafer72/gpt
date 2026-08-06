<?php

namespace App\Infrastructure\LLM\Cloudflare;

use Illuminate\Support\Facades\Http;
use App\Domain\Contracts\LlmProvider;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;

class CloudflareProvider implements LlmProvider
{
    protected string $accountId;
    protected string $token;
    protected string $model;


    public function __construct()
    {
        $this->accountId = config(
            'services.cloudflare.account_id'
        );

        $this->token = config(
            'services.cloudflare.api_token'
        );

        $this->model = config(
            'services.cloudflare.models.chat',
            '@cf/qwen/qwen3-30b-a3b-fp8'
        );
    }


    protected function url(): string
    {
        return sprintf(
            '%s/%s/ai/run/%s',
            config('services.cloudflare.base_url'),
            $this->accountId,
            $this->model
        );
    }


    protected function headers(): array
    {
        return [
            'Authorization' =>
                'Bearer '.$this->token,

            'Content-Type' =>
                'application/json',
        ];
    }


    public function chat(
        ChatRequest $request
    ): ChatResponse {

        $messages = array_map(
            fn($message) => $message->toArray(),
            $request->messages
        );


        $response = Http::withHeaders(
            $this->headers()
        )
        ->post(
            $this->url(),
            [
                'messages' => $messages,

                'temperature' =>
                    $request->temperature,

                'max_tokens' =>
                    $request->maxTokens ?? 1000,
            ]
        );


        if (!$response->successful()) {

            throw new \Exception(
                'Cloudflare AI Error: '.$response->body()
            );

        }


        $data = $response->json();


        $content = data_get(
            $data,
            'result.response',
            ''
        );


        $inputTokens = (int) ceil(
            mb_strlen(
                json_encode($messages)
            ) / 4
        );


        $outputTokens = (int) ceil(
            mb_strlen($content) / 4
        );


        return new ChatResponse(

            content: $content,

            usage: new Usage(
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
                totalTokens: $inputTokens + $outputTokens
            ),

            model: $this->model
        );
    }



    public function stream(
        ChatRequest $request
    ) {

        $messages = array_map(
            fn($message) => $message->toArray(),
            $request->messages
        );


        $response = Http::withHeaders(
            $this->headers()
        )
        ->post(
            $this->url(),
            [
                'stream' => true,

                'messages' => $messages,

                'temperature' =>
                    $request->temperature,

                'max_tokens' =>
                    $request->maxTokens ?? 1000,
            ]
        );


        if (!$response->successful()) {

            throw new \Exception(
                'Cloudflare AI Error: '.$response->body()
            );

        }


        return (function () use ($response) {


            foreach (
                explode(
                    "\n",
                    $response->body()
                )
                as $line
            ) {


                $line = trim($line);


                if (
                    empty($line) ||
                    !str_starts_with(
                        $line,
                        'data:'
                    )
                ) {
                    continue;
                }


                $json = json_decode(
                    trim(
                        str_replace(
                            'data:',
                            '',
                            $line
                        )
                    ),
                    true
                );


                if (!$json) {
                    continue;
                }


                $text = data_get(
                    $json,
                    'response',
                    ''
                );


                if (!$text) {
                    continue;
                }


                yield [
                    'choices' => [
                        [
                            'delta' => [
                                'content' => $text
                            ]
                        ]
                    ]
                ];

            }


        })();

    }
}
