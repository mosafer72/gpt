<?php

namespace App\Application\Services\Chat;


use App\Application\Context\ChatContext;
use App\Application\Pipelines\ChatPipeline;


class ChatService
{


    public function __construct(
        protected ChatPipeline $pipeline
    )
    {

    }



    public function chat(
        ChatContext $context
    ): ChatResult {


        $context = $this->pipeline
            ->handle($context);


        return new ChatResult(
            response: $context->assistantResponse,
            usage: $context->tokenUsage
        );

    }

}
