<?php

namespace App\Domain\Contracts;

use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;

interface LLMProvider
{
    public function chat(
        ChatRequest $request
    ): ChatResponse;
}
