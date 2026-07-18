<?php

namespace App\Infrastructure\LLM;


use App\Models\AiModel;
use App\Models\LLMRequest;
use Log;
use OpenAI;

use App\Domain\Contracts\LlmProvider;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;



class GapGPTProvider implements LlmProvider
{


    /**
     * ساخت Client بر اساس Provider دیتابیس
     */
    protected function client(AiModel $model)
    {

        return OpenAI::factory()

            ->withApiKey(
                $model->provider->api_key
            )

            ->withBaseUri(
                $model->provider->base_url
            )

            ->make();

    }






    /**
     * Stream Response
     */
    public function stream(ChatRequest $request)
    {


        $aiModel = AiModel::with('provider')

            ->where(
                'key',
                $request->model
            )

            ->firstOrFail();

        \Log::info('AI MODEL DEBUG', [
            'requested_model' => $request->model,
        ]);


        $client = $this->client($aiModel);




        return $client
            ->chat()
            ->createStreamed([


                // مدل واقعی API
                'model' =>
                    $aiModel->model_name,



                'messages' => array_map(

                    fn($message) =>
                    $message->toArray(),

                    $request->messages

                ),



                'temperature' =>
                    $request->temperature,



                'max_tokens' =>
                    $request->maxTokens,



                'stream' => true,



                'stream_options' => [

                    'include_usage' => true

                ]


            ]);

    }










    /**
     * Chat Request
     */
    public function chat(
        ChatRequest $request
    ): ChatResponse {


        /*
        |--------------------------------------------------------------------------
        | Load Model + Provider
        |--------------------------------------------------------------------------
        */


        $aiModel = AiModel::with('provider')

            ->where(
                'key',
                $request->model
            )

            ->firstOrFail();






        /*
        |--------------------------------------------------------------------------
        | Create API Client
        |--------------------------------------------------------------------------
        */


        $client = $this->client($aiModel);








        /*
        |--------------------------------------------------------------------------
        | Save Request Log
        |--------------------------------------------------------------------------
        */
        $messages = array_map(

            fn($message) =>
            $message->toArray(),

            $request->messages

        );


        Log::info('LLM REQUEST DEBUG', [

            'base_url' => $aiModel->provider->base_url,

            'provider' => $aiModel->provider->key,

            'model' => $aiModel->model_name,

            'messages' => $messages,

            'temperature' => $request->temperature,

            'max_tokens' => $request->maxTokens

        ]);

        $log = LLMRequest::create([


            'user_id' => auth()->id(),


            'provider' =>
                $aiModel->provider->key,



            'model' =>
                $aiModel->model_name,



            'messages' => collect(
                $request->messages
            )

                ->map(
                    fn($message) =>
                    $message->toArray()
                )

                ->toArray(),




            'request_options' => [

                'temperature' =>
                    $request->temperature,


                'max_tokens' =>
                    $request->maxTokens,

            ],



            'status' => 'processing'


        ]);








        /*
        |--------------------------------------------------------------------------
        | Send Request
        |--------------------------------------------------------------------------
        */


        $response = $client

            ->chat()

            ->create([


                'model' =>
                    $aiModel->model_name,



                'messages' => array_map(

                    fn($message) =>
                    $message->toArray(),

                    $request->messages

                ),



                'temperature' =>
                    $request->temperature,



                'max_tokens' =>
                    $request->maxTokens,


            ]);









        /*
        |--------------------------------------------------------------------------
        | Update Log
        |--------------------------------------------------------------------------
        */


        $log->update([


            'status' => 'completed',



            'response' => [

                'content' =>

                    $response
                        ->choices[0]
                        ->message
                        ->content

            ],




            'usage' => [


                'input' =>

                    $response
                        ->usage
                        ->promptTokens ?? 0,



                'output' =>

                    $response
                        ->usage
                        ->completionTokens ?? 0,



                'total' =>

                    $response
                        ->usage
                        ->totalTokens ?? 0,


            ]


        ]);









        /*
        |--------------------------------------------------------------------------
        | Return Domain Response
        |--------------------------------------------------------------------------
        */


        return new ChatResponse(



            content:

            $response
                ->choices[0]
                ->message
                ->content,





            usage:

            new Usage(


                inputTokens:

                $response
                    ->usage
                    ->promptTokens ?? 0,



                outputTokens:

                $response
                    ->usage
                    ->completionTokens ?? 0,



                totalTokens:

                $response
                    ->usage
                    ->totalTokens ?? 0,


            ),





            model:

            $aiModel->model_name


        );


    }



}
