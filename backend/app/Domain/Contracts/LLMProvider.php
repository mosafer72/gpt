<?php

namespace App\Domain\Contracts;

use App\Domain\LLM\ChatRequest;

use App\Domain\LLM\ChatResponse;

interface LlmProvider

{

    public function chat(

        ChatRequest $request

    ): ChatResponse;

    public function stream(

        ChatRequest $request

    );
}
