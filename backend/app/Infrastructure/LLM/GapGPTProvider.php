<?php

namespace App\Infrastructure\LLM;

use App\Models\LLMRequest;
use OpenAI;
use App\Domain\Contracts\LLMProvider;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;

class GapGPTProvider implements LLMProvider
{
    protected $client;


    public function __construct()
    {
        $this->client = OpenAI::factory()

            ->withApiKey(
                config('services.gapgpt.key')
            )

            ->withBaseUri(
                config('services.gapgpt.base_url')
            )

            ->make();
    }


public function stream(ChatRequest $request)

{



    return $this->client->chat()->createStreamed([

        'model' => $request->model,

        'messages' => array_map(

            fn($message) => $message->toArray(),

            $request->messages

        ),

        'temperature' => $request->temperature,

        'max_tokens' => $request->maxTokens,

        'stream' => true,

    ]);

}



    public function chat(
        ChatRequest $request
    ): ChatResponse {

        $log = LLMRequest::create([
            'provider' => 'openai',
            'model' => $request->model,
            'messages' => $request->messages,
            'request_options' => $request->toArray(),
            'status' => 'processing'
        ]);


        $response = $this->client
            ->chat()
            ->create([

                'model' => $request->model,


                'messages' => array_map(
                    fn($message) => $message->toArray(),
                    $request->messages
                ),


                'temperature' => $request->temperature,


                'max_tokens' => $request->maxTokens,

            ]);




        $llmresponse = LLMRequest::create([
            'user_id' => auth()->id() ?? null,
            'provider' => 'gapgpt',
            'model' => $request->model,
            'conversation_id' => $context->conversationId ?? null,

            'messages' => $request->messages,

            'request_options' => [
                'temperature' => $request->temperature,
                'max_tokens' => $request->maxTokens,
            ],

            'status' => 'processing',
        ]);




        return new ChatResponse(

            content:
            $response
                ->choices[0]
                ->message
                ->content,


            usage:
            new Usage(

                inputTokens:
                $response->usage->promptTokens ?? 0,


                outputTokens:
                $response->usage->completionTokens ?? 0,


                totalTokens:
                $response->usage->totalTokens ?? 0,

            ),


            model:
            $request->model

        );
    }
}
