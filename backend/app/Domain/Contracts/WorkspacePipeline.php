<?php

namespace App\Domain\Contracts;

use App\Application\Context\ChatContext;

interface WorkspacePipeline
{
    public function handle(ChatContext $context):mixed;

    public function stream(ChatContext $context,callable $send):void;
}
