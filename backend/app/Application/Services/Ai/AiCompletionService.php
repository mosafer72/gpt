<?php

namespace App\Application\Services\Ai;

use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatMessage; // <--- این را اضافه کنید
use App\Domain\Contracts\SmallChatProvider;

class AiCompletionService
{
    public function __construct(
        protected SmallChatProvider $provider,
    ) {
    }

    public function complete(
        string $prompt,
        ?string $model = null
    ): string {

        // تبدیل پرامپت متنی به شیء ChatMessage
        $request = new ChatRequest(
            messages: [
                ChatMessage::user($prompt) // <--- اصلاح اصلی اینجاست
            ],
            model: $model
        );

        $response = $this->provider->chat(
            $request
        );

        return $response->content;
    }
}
