<?php

namespace App\Application\Agents\Contracts;

use App\Application\Context\ChatContext;
use App\Models\User;

interface AgentTool
{
    public function name(): string;

    public function description(): string;

    public function schema(): array;

    public function execute(
        ChatContext $context,
        User $user,
        array $arguments = []
    ): mixed;
}
