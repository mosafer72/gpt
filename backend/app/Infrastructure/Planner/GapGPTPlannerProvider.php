<?php

namespace App\Infrastructure\Planner;

use App\Application\DTOs\IntentResult;
use App\Domain\Contracts\PlannerProvider;
use OpenAI;

class GapGPTPlannerProvider implements PlannerProvider
{
    private $client;

    public function __construct()
    {
        \Log::info('Planner Config', [
            'url' => config('services.planner.base_url'),
            'model' => config('services.planner.model'),
        ]);

        $this->client = OpenAI::factory()
            ->withApiKey(config('services.planner.api_key'))
            ->withBaseUri(config('services.planner.base_url'))
            ->withHttpClient(
                new \GuzzleHttp\Client([
                    'timeout' => 120,
                    'connect_timeout' => 20,
                ])
            )
            ->make();
    }

    public function analyze(string $message): IntentResult
    {

        $prompt = <<<PROMPT

Analyze the user request.

Return ONLY valid JSON:

{

  "intent": "chat|pdf|xlsx|image|docx",

  "confidence": 0.0,

  "rewrite": "...",

  "needs_clarification": false

}

User: {$message}

PROMPT;

        $response = $this->client->chat()->create([

            'model' => config('services.planner.model'),

            'messages' => [

                [
                    'role' => 'system',
                    'content' => 'You are a request classifier.'
                ],

                [
                    'role' => 'user',
                    'content' => $prompt
                ],

            ],

            'temperature' => 0,

            'max_tokens' => 200,

        ]);

        $content = trim($response->choices[0]->message->content);

        // اگر مدل داخل ```json برگرداند

        $content = preg_replace('/^```json\s*/', '', $content);

        $content = preg_replace('/^```\s*/', '', $content);

        $content = preg_replace('/\s*```$/', '', $content);

        $data = json_decode($content, true);

        if (!is_array($data)) {

            return new IntentResult(

                intent: 'chat',

                confidence: 0.5,

                rewrite: $message,

                needsClarification: true,

            );

        }

        return new IntentResult(

            intent: $data['intent'] ?? 'chat',

            confidence: (float) ($data['confidence'] ?? 0.5),

            rewrite: $data['rewrite'] ?? $message,

            needsClarification: (bool) ($data['needs_clarification'] ?? false),

        );

    }

}
