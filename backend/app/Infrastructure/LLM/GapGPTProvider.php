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
    protected function client(AiModel $model)
    {
        return OpenAI::factory()->withApiKey($model->provider->api_key)->withBaseUri($model->provider->base_url)->make();
    }

    public function stream(ChatRequest $request)
    {
        // جستجوی امن بر اساس key یا model_name
        $aiModel = AiModel::with('provider')
            ->where('key', $request->model)
            ->orWhere('model_name', $request->model)
            ->firstOrFail();

        $client = $this->client($aiModel);

        $payload = [
            'model' => $aiModel->model_name,
            'messages' => array_map(fn($message) => $message->toArray(), $request->messages),
            'temperature' => $request->temperature ?? 0.7,
            'max_tokens' => $request->maxTokens ?? 1000,
            'stream' => true, // <--- بسیار مهم: حتما true باشد
        ];

        // غیرفعال کردن ارسال reasoning مجزا در OpenRouter برای دریافت مستقیم پاسخ
        if (str_contains($aiModel->provider->base_url ?? '', 'openrouter')) {
            $payload['include_reasoning'] = false;
        }

        $stream = $client->chat()->createStreamed($payload);

        return (function () use ($stream) {
            foreach ($stream as $chunk) {

                yield $chunk;
            }
        })();
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $aiModel = AiModel::with('provider')
            ->where('key', $request->model)
            ->orWhere('model_name', $request->model)
            ->firstOrFail();

        $client = $this->client($aiModel);
        $messages = array_map(fn($message) => $message->toArray(), $request->messages);

        $log = LLMRequest::create([
            'user_id' => auth()->id(),
            'provider' => $aiModel->provider->key,
            'model' => $aiModel->model_name,
            'messages' => $messages,
            'request_options' => [
                'temperature' => $request->temperature ?? 0.7,
                'max_tokens' => $request->maxTokens ?? 1000
            ],
            'status' => 'processing'
        ]);

        try {
            $response = $client->chat()->create([
                'model' => $aiModel->model_name,
                'messages' => $messages,
                'temperature' => $request->temperature ?? 0.7,
                'max_tokens' => $request->maxTokens ?? 1000
            ]);


        } catch (\Throwable $e) {
            \Log::error('LLM ERROR', ['message' => $e->getMessage()]);
            $log->update(['status' => 'failed']);
            throw $e;
        }

        $input = $response->usage->promptTokens ?? 0;
        $output = $response->usage->completionTokens ?? 0;
        $total = $response->usage->totalTokens ?? 0;

        $content = $response->choices[0]->message->content ?? '';

        $log->update([
            'status' => 'completed',
            'response' => ['content' => $content],
            'usage' => ['input' => $input, 'output' => $output, 'total' => $total]
        ]);

        return new ChatResponse(
            content: $content,
            usage: new Usage(inputTokens: $input, outputTokens: $output, totalTokens: $total),
            model: $aiModel->model_name
        );
    }
}
