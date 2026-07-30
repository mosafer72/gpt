<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;

class DocumentStructureRepository
{
    protected string $table = 'document_structure';

    public function store(array $data): array
    {
        $documentId = (string) ($data['document_id'] ?? '');

        $bindings = [
            'id' => $documentId,
            'document_id' => $documentId,
            'workspace_id' => (string) ($data['workspace_id'] ?? ''),
            'title' => $this->toObject($data['title'] ?? null),
            'sections' => array_values($data['sections'] ?? []),
            'statistics' => $this->toObject($data['statistics'] ?? null),
            'metadata' => $this->toObject($data['metadata'] ?? null),
            'created_at' => $data['created_at'] ?? now()->toISOString(),
        ];

        $response = Surreal::query(new BoundQuery(
            query: "
                UPSERT type::record('{$this->table}', \$id)
                SET
                    document_id = <string>\$document_id,
                    workspace_id = <string>\$workspace_id,
                    title = \$title,
                    sections = \$sections,
                    statistics = \$statistics,
                    metadata = \$metadata,
                    created_at = type::datetime(\$created_at),
                    updated_at = time::now();
            ",
            bindings: $bindings
        ));

        return $this->format($response);
    }

    public function findByDocument(string $documentId): array
    {
        $response = Surreal::query(new BoundQuery(
            query: "SELECT * FROM {$this->table} WHERE document_id = \$document_id LIMIT 1;",
            bindings: ['document_id' => (string) $documentId]
        ));

        $result = $this->format($response);

        return $result[0] ?? [];
    }

    public function findByWorkspace(string $workspaceId): array
    {
        $response = Surreal::query(new BoundQuery(
            query: "SELECT * FROM {$this->table} WHERE workspace_id = \$workspace_id ORDER BY created_at DESC;",
            bindings: ['workspace_id' => (string) $workspaceId]
        ));

        return $this->format($response);
    }

    public function sections(string $documentId): array
    {
        return $this->findByDocument($documentId)['sections'] ?? [];
    }

    public function findSection(string $documentId, string $section): array
    {
        return collect($this->sections($documentId))
            ->first(
                fn($item) => strtolower($item['name'] ?? '') === strtolower($section)
            ) ?? [];
    }

    public function deleteDocument(string $documentId): mixed
    {
        return Surreal::query(new BoundQuery(
            query: "DELETE FROM {$this->table} WHERE document_id = \$document_id;",
            bindings: ['document_id' => (string) $documentId]
        ));
    }

    public function debugDocument(string $documentId): array
    {
        return $this->findByDocument($documentId);
    }

    protected function toObject(mixed $value): array
    {
        if (is_array($value) && !empty($value)) {
            return $value;
        }

        if ($value instanceof \stdClass) {
            $array = (array) $value;
            if (!empty($array)) {
                return $array;
            }
        }

        return ['_empty' => true];
    }

    protected function format(mixed $response): array
    {
        if (empty($response)) {
            return [];
        }

        if (is_object($response) && method_exists($response, 'getData')) {
            $response = $response->getData();
        }

        if (!is_array($response)) {
            return [];
        }

        if (isset($response[0]['result']) && is_array($response[0]['result'])) {
            return collect($response[0]['result'])->values()->all();
        }

        return collect($response)->values()->all();
    }
}
