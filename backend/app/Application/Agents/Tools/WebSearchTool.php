<?php

namespace App\Application\Agents\Tools;

use App\Application\Agents\Contracts\AgentTool;
use App\Application\Context\ChatContext;
use App\Application\Tools\WebSearchTool as SearchTool;
use App\Models\User;

class WebSearchTool implements AgentTool
{
    public function __construct(
        protected SearchTool $search
    ) {
    }

    public function name(): string
    {
        return 'web_search';
    }

    public function description(): string
    {
        return 'Search the web for fresh, current, real-time, or external information that is not reliably available in the conversation context.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The exact query to search on the web.',
                ],
            ],
            'required' => [
                'query',
            ],
        ];
    }

    public function execute(
        ChatContext $context,
        User $user,
        array $arguments = []
    ): array {
        $query = $arguments['query'] ?? '';

        if (!is_string($query)) {
            throw new \InvalidArgumentException(
                'web_search query must be a string.'
            );
        }

        $query = trim($query);

        if ($query === '') {
            throw new \InvalidArgumentException(
                'web_search requires a non-empty query.'
            );
        }

        $results = $this->search->execute([
            'query' => $query,
        ]);

        if (!is_array($results)) {
            $results = [];
        }

        $context->addMetadata(
            'last_web_search',
            [
                'query' => $query,
                'results' => $results,
            ]
        );

        return [
            'query' => $query,
            'results' => $results,
            'count' => count($results),
        ];
    }
}
