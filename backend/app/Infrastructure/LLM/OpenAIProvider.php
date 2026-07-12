    <?php

    namespace App\Infrastructure\LLM;

    use App\Models\LLMRequest;
    use OpenAI;
    use App\Domain\Contracts\LLMProvider;
    use App\Domain\LLM\ChatRequest;
    use App\Domain\LLM\ChatResponse;
    use App\Domain\LLM\Usage;

    class OpenAIProvider implements LLMProvider
    {
        protected $client;


        public function __construct()
        {
            $this->client = OpenAI::client(
                config('services.openai.key')
            );
        }


        public function chat(
            ChatRequest $request
        ): ChatResponse {

            $log = LLMRequest::create([
                    'provider'=>'openai',
                    'model'=>$request->model,
                    'messages'=>$request->messages,
                    'request_options'=>$request->toArray(),
                    'status'=>'processing'
                ]);

            $response = $this->client
                ->chat()
                ->create([

                    'model' => $request->model,


                    'messages' => array_map(
                        fn ($message) => $message->toArray(),
                        $request->messages
                    ),


                    'temperature' => $request->temperature,


                    'max_tokens' => $request->maxTokens,

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
