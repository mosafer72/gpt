<?php

namespace App\Infrastructure\Planner;

use App\Application\DTOs\IntentResult;
use App\Domain\Contracts\PlannerProvider;
use OpenAI;
use App\Application\Context\ChatContext;

use App\Models\Usage;

class GapGPTPlannerProvider implements PlannerProvider
{
    private $client;

    public function __construct()
    {
        \Log::info('Planner Config', [
            'url' => config('services.planner.base_url'),
            'model' => config('services.planner.model'),
            'api_key' => config('services.planner.key'),
        ]);


        $this->client = OpenAI::factory()
            ->withApiKey(
                config('services.planner.key')
            )
            ->withBaseUri(
                config('services.planner.base_url')
            )
            ->withHttpClient(
                new \GuzzleHttp\Client([
                    'timeout' => 120,
                    'connect_timeout' => 20,
                    'headers' => [
                        'HTTP-Referer' => 'http://localhost',
                        'X-Title' => 'GapGPT',
                    ],
                ])
            )
            ->make();
    }

    public function analyze(ChatContext $context): IntentResult
    {

        \Log::info('Planner analyze executed');


        $prompt = <<<PROMPT

Analyze the user request.

Return ONLY valid JSON:

{

  "intent": "chat|pdf|xlsx|image|docx",

  "confidence": 0.0,

  "rewrite": "...",

  "needs_clarification": false

}

User: {$context->currentMessage}

PROMPT;
        $start = microtime(true);

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




        Usage::create([

            'user_id' => (int) $context->userId,

            'conversation_id' => (int) $context->conversationId,

            'type' => 'planner',

            'model' => config('services.planner.model'),

            'prompt_tokens' => $response->usage->promptTokens ?? 0,

            'completion_tokens' => $response->usage->completionTokens ?? 0,

            'total_tokens' => $response->usage->totalTokens ?? 0,

        ]);

        \Log::info('Planner Time', [
            'seconds' => microtime(true) - $start
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

                rewrite: $context->currentMessage,

                needsClarification: true,

            );

        }

        return new IntentResult(

            intent: $data['intent'] ?? 'chat',

            confidence: (float) ($data['confidence'] ?? 0.5),

            rewrite: $data['rewrite'] ?? $context->currentMessage,

            needsClarification: (bool) ($data['needs_clarification'] ?? false),

        );

    }

}
