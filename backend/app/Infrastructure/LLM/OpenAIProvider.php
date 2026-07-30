<?php

namespace App\Infrastructure\LLM;

use Throwable;
use OpenAI;
use Illuminate\Support\Facades\Log;
use App\Models\LLMRequest;
use App\Domain\Contracts\LlmProvider;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;

class OpenAIProvider implements LlmProvider
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(
            config('services.openai.key')
        );
    }

    /**
     * Stream Response
     */
    public function stream(ChatRequest $request)
    {
        return $this->client
            ->chat()
            ->createStreamed([

                'model' => $request->model,

                'messages' => $this->prepareMessages($request),

                'temperature' => $request->temperature,

                'max_tokens' => $request->maxTokens,

                'stream' => true,

                'stream_options' => [
                    'include_usage' => true,
                ],

            ]);
    }

    /**
     * Normal Chat
     */
    public function chat(
        ChatRequest $request
    ): ChatResponse {

        $log = LLMRequest::create([

            'provider' => 'openai',

            'model' => $request->model,

            'messages' => $this->prepareMessages($request),

            'request_options' => [

                'temperature' => $request->temperature,

                'max_tokens' => $request->maxTokens,

            ],

            'status' => 'processing',

        ]);

        try {

            $response = $this->client

                ->chat()

                ->create([

                    'model' => $request->model,

                    'messages' => $this->prepareMessages($request),

                    'temperature' => $request->temperature,

                    'max_tokens' => $request->maxTokens,

                ]);

            $content =

                $response
                    ->choices[0]
                    ->message
                    ->content;

            $usage = new Usage(

                inputTokens:
                    $response->usage->promptTokens ?? 0,

                outputTokens:
                    $response->usage->completionTokens ?? 0,

                totalTokens:
                    $response->usage->totalTokens ?? 0,

            );

            $log->update([

                'status' => 'completed',

                'response' => [

                    'content' => $content,

                ],

                'usage' => [

                    'input' => $usage->inputTokens,

                    'output' => $usage->outputTokens,

                    'total' => $usage->totalTokens,

                ],

            ]);

            return new ChatResponse(

                content: $content,

                usage: $usage,

                model: $request->model,

            );

        } catch (Throwable $e) {

            $log->update([

                'status' => 'failed',

                'response' => [

                    'error' => $e->getMessage(),

                ],

            ]);

            Log::error('OPENAI ERROR', [

                'message' => $e->getMessage(),

            ]);

            throw $e;
        }
    }

    /**
     * Convert Domain Messages
     */
    protected function prepareMessages(
        ChatRequest $request
    ): array {

        return collect(
            $request->messages
        )

            ->map(fn($message) => [

                'role' => $message->role,

                'content' => $message->content,

            ])

            ->values()

            ->toArray();
    }
}
