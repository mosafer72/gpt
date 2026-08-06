<?php

namespace App\Application\AI\Contracts;

use App\Domain\LLM\ChatRequest;

interface LlmProviderInterface
{

    public function chat(
        ChatRequest $request
    );


    public function stream(
        ChatRequest $request
    );

}
