<?php

namespace App\Application\Agents\Tools;

use App\Application\Agents\Contracts\AgentTool;
use App\Application\Context\ChatContext;
use App\Domain\Contracts\ConversationLoader;
use App\Models\User;

class MoreHistoryTool implements AgentTool
{
    public function __construct(
        protected ConversationLoader $conversationLoader
    ) {
    }

    public function name(): string
    {
        return 'more_history';
    }

    public function description(): string
    {
        return 'Load more conversation history when the current context is not sufficient to understand the user request.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'amount' => [
                    'type' => 'integer',
                    'description' => 'Additional number of messages to load.',
                    'minimum' => 10,
                    'maximum' => 30,
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(
        ChatContext $context,
        User $user,
        array $arguments = []
    ): array {
        $current = (int) (
            $context->getMetadata('history_limit') ?? 0
        );

        $amount = (int) (
            $arguments['amount'] ?? 10
        );

        $amount = max(10, min(30, $amount));

        $limit = min(
            $current + $amount,
            100
        );

        $messages = $this->conversationLoader->load(
            $context->conversationId,
            $limit
        );

        $context->setMessages($messages);

        $context->addMetadata(
            'history_limit',
            $limit
        );

        return [
            'success' => true,
            'history_limit' => $limit,
            'messages_loaded' => count($messages),
        ];
    }
}
