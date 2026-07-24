<?php

namespace App\Infrastructure\LLM;

use App\Domain\Contracts\SmallChatProvider;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OllamaQwenChatProvider implements SmallChatProvider
{
    protected Client $client;
    protected string $url = 'http://127.0.0.1:11434/api/chat';

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => 300,
            'connect_timeout' => 3,
        ]);
    }

    public function stream(ChatRequest $request): \Generator
    {
        $requestStart = microtime(true);
        $firstTokenTime = null;

        Log::info('OLLAMA REQUEST', [
            'model'    => $request->model,
            'messages' => $this->prepareMessages($request),
        ]);

        $response = $this->client->post(
            $this->url,
            [
                'stream' => true, // فعال‌سازی استریم لایه Guzzle
                'json'   => [
                    'model'      => $request->model,
                    'messages'   => $this->prepareMessages($request),
                    'stream'     => true,
                    'keep_alive' => '24h',
                    'options'    => [
                        'temperature' => 0.4,
                        'num_predict' => 2048,
                        'num_ctx'     => 4096,
                    ],
                ]
            ]
        );

        // تبدیل بفر Guzzle به یک Resource واقعی در PHP
        $stream = $response->getBody()->detach();

        if (!is_resource($stream)) {
            return;
        }

        // خواندن خط به خط بلافاصله بعد از رسیدن newline (\n) از سمت اولاما
        while (!feof($stream)) {
            $line = fgets($stream);

            if ($line === false) {
                continue;
            }

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $data = json_decode($line, true);

            if (!is_array($data)) {
                continue;
            }

            if (($data['done'] ?? false) === true) {
                $total = round((microtime(true) - $requestStart) * 1000, 2);
                $generation = $firstTokenTime
                    ? round((microtime(true) - $firstTokenTime) * 1000, 2)
                    : 0;

                Log::info('OLLAMA PERFORMANCE', [
                    'model'         => $request->model,
                    'ttft_ms'       => $firstTokenTime ? round(($firstTokenTime - $requestStart) * 1000, 2) : null,
                    'generation_ms' => $generation,
                    'total_ms'      => $total,
                ]);

                fclose($stream);
                return;
            }

            $content = $data['message']['content'] ?? null;

            if ($content === null || $content === '') {
                continue;
            }

            if ($firstTokenTime === null) {
                $firstTokenTime = microtime(true);
            }

            // ارسال آنی توکن تولید شده به لایه‌های بالاتر
            yield [
                'content' => $content
            ];
        }

        fclose($stream);
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $content = '';

        foreach ($this->stream($request) as $chunk) {
            $content .= $chunk['content'];
        }

        return new ChatResponse(
            content: $content,
            usage: new Usage(
                inputTokens: 0,
                outputTokens: 0,
                totalTokens: 0
            )
        );
    }

    protected function prepareMessages(ChatRequest $request): array
    {
        return collect($request->messages)
            ->map(fn($message) => [
                'role'    => $message->role,
                'content' => $message->content
            ])
            ->values()
            ->toArray();
    }
}
