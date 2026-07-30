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
    protected string $baseUrl;

    public function __construct(?Client $client = null)
    {
        // خواندن آدرس از config و تعریف مقدار پیش‌فرض
        $this->baseUrl = config('services.ollama.url', 'http://127.0.0.1:11434');

        $this->client = $client ?? new Client([
            'timeout' => 300,
            'connect_timeout' => 5,
        ]);
    }

    public function stream(ChatRequest $request): \Generator
    {
        $requestStart = microtime(true);
        $firstTokenTime = null;


        try {
            $response = $this->client->post(
                rtrim($this->baseUrl, '/') . '/api/chat',
                [
                    'stream' => true, // الزام برای استریم واقعی در Guzzle
                    'json' => [
                        'model' => $request->model,
                        'messages' => $this->prepareMessages($request),
                        'stream' => true,
                        'keep_alive' => '24h',
                        'options' => [
                            'temperature' => $request->temperature ?? 0.1,
                            'num_predict' => $request->maxTokens ?? 4096,
                            'num_ctx' => 32768
                        ]
                    ]
                ]
            );
        } catch (\Throwable $e) {
            Log::error('OLLAMA REQUEST FAILED', ['error' => $e->getMessage()]);
            throw $e;
        }

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            // خواندن دیتا و اضافه کردن به بفر
            $buffer .= $body->read(1024);

            // پردازش خط به خط برای جلوگیری از شکستن JSON
            while (($pos = strpos($buffer, "\n")) !== false) {
                $jsonLine = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($jsonLine === '') {
                    continue;
                }

                $data = json_decode($jsonLine, true);

                if (!is_array($data)) {
                    continue;
                }

                // زمان رسیدن به انتهای پاسخ
                if (($data['done'] ?? false) === true) {
                    $totalMs = round((microtime(true) - $requestStart) * 1000, 2);
                    $ttftMs = $firstTokenTime ? round(($firstTokenTime - $requestStart) * 1000, 2) : null;

                    // ارسال اطلاعات توکن‌ها به عنوان آخرین چانک متاداده
                    yield [
                        'content' => '',
                        'usage' => new Usage(
                            inputTokens: $data['prompt_eval_count'] ?? 0,
                            outputTokens: $data['eval_count'] ?? 0,
                            totalTokens: ($data['prompt_eval_count'] ?? 0) + ($data['eval_count'] ?? 0)
                        )
                    ];

                    return;
                }

                $content = $data['message']['content'] ?? null;

                if ($content !== null && $content !== '') {
                    if ($firstTokenTime === null) {
                        $firstTokenTime = microtime(true);
                    }

                    yield [
                        'content' => $content
                    ];
                }
            }
        }
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $content = '';
        $finalUsage = new Usage(0, 0, 0);

        foreach ($this->stream($request) as $chunk) {
            $content .= $chunk['content'];

            // اگر چانک شامل اطلاعات توکن بود، آن را ذخیره کن
            if (isset($chunk['usage'])) {
                $finalUsage = $chunk['usage'];
            }
        }

        return new ChatResponse(
            content: $content,
            usage: $finalUsage,
            model: $request->model
        );
    }

    protected function prepareMessages(ChatRequest $request): array
    {
        return collect($request->messages)
            ->map(fn($message) => [
                'role' => $message->role,
                'content' => $message->content
            ])
            ->values()
            ->toArray();
    }
}
