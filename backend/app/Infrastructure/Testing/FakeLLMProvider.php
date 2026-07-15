<?php

namespace App\Infrastructure\Testing;

use App\Domain\Contracts\LlmProvider;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Domain\LLM\Usage;

class FakeLlmProvider implements LlmProvider
{
    public function chat(
        ChatRequest $request
    ): ChatResponse {

        return new ChatResponse(

            content: "Fake LLM response",

            usage: new Usage(
                inputTokens: 10,
                outputTokens: 20,
                totalTokens: 30
            ),

            model: "fake-model"

        );
    }
}
