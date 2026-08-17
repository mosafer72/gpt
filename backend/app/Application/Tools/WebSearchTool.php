<?php

namespace App\Application\Tools;

use App\Domain\Contracts\SearchProvider;
use App\Domain\Contracts\Tool;

class WebSearchTool implements Tool
{
    public function __construct(
        protected SearchProvider $provider
    ) {
    }

    public function name(): string
    {
        return 'web_search';
    }

    public function enabled(): bool
    {
        return true;
    }

    public function execute(array $input): array
    {
        $query = $input['query'] ?? '';

        if (is_array($query)) {
            $query = $query['text']
                ?? $query['query']
                ?? '';
        }

        if (!is_string($query)) {
            return [];
        }

        $query = trim($query);

        if ($query === '') {
            return [];
        }

        \Log::info('WEB SEARCH TOOL', [
            'query' => $query,
        ]);

        $result = $this->provider->search($query);

        if (!is_array($result)) {
            $result = [];
        }

        \Log::info('WEB SEARCH OUTPUT', [
            'query' => $query,
            'count' => count($result),
            'result' => $result,
        ]);

        return $result;
    }
}
