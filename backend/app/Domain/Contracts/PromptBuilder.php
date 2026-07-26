<?php

namespace App\Domain\Contracts;


use App\Application\Context\ChatContext;



interface PromptBuilder
{


    public function build(
        ChatContext $context
    ): array;


}
