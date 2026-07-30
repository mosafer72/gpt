<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;

class DocumentVectorRepository
{
    protected string $table = 'document_embedding';

    public function store(array $data): array
    {
        $response = Surreal::query(new BoundQuery(
            query: "
                UPSERT type::record('document_embedding', \$id)
                SET
                    document_id = <string>\$document_id,
                    workspace_id = <string>\$workspace_id,
                    chunk_id = <string>\$chunk_id,
                    content = <string>\$content,
                    embedding = \$embedding,
                    metadata = \$metadata,
                    created_at = IF created_at IS NONE THEN type::datetime(\$created_at) ELSE created_at END,
                    updated_at = time::now();
                RETURN AFTER;
            ",
            bindings: [
                'id' => $this->recordId($data),
                ...$this->preparePayload($data)
            ]
        ));

        return $this->format($response);
    }

    public function storeBulk(array $vectors): void
    {
        foreach ($vectors as $vector) {
            $this->store($vector);
        }
    }

    public function countWorkspace(string $workspaceId): int
    {
        $response = Surreal::query(new BoundQuery(
            query: "SELECT count() AS total FROM {$this->table} WHERE workspace_id = \$workspace_id GROUP ALL;",
            bindings: ['workspace_id' => (string) $workspaceId]
        ));

        $result = $this->format($response);
        return (int) ($result[0]['total'] ?? 0);
    }

    public function findByDocument(string $documentId): array
    {
        return $this->format(Surreal::query(new BoundQuery(
            query: "SELECT * FROM {$this->table} WHERE document_id = \$document_id;",
            bindings: ['document_id' => (string) $documentId]
        )));
    }

    public function deleteDocument(string $documentId): mixed
    {
        return Surreal::query(new BoundQuery(
            query: "DELETE FROM {$this->table} WHERE document_id = \$document_id;",
            bindings: ['document_id' => (string) $documentId]
        ));
    }

    public function search(string $workspaceId, array $vector, int $limit = 10, array $documentIds = []): array
    {
        $bindings = [
            'workspace_id' => (string) $workspaceId,
            'embedding' => array_values(array_map('floatval', $vector)),
            'limit' => (int) $limit
        ];

        $filter = '';

        if (!empty($documentIds)) {
            $bindings['document_ids'] = array_values(array_map(fn($id) => (string) $id, $documentIds));
            $filter = 'AND document_id IN $document_ids';
        }

        $response = Surreal::query(new BoundQuery(
            query: "
                SELECT *, vector::similarity::cosine(embedding, \$embedding) AS score
                FROM {$this->table}
                WHERE workspace_id = \$workspace_id {$filter}
                ORDER BY score DESC
                LIMIT \$limit;
            ",
            bindings: $bindings
        ));

        return collect($this->format($response))->map(function ($item) {
            $item['score'] = (float) ($item['score'] ?? 0);
            return $item;
        })->values()->all();
    }

    protected function preparePayload(array $data): array
    {
        return [
            'document_id' => (string) ($data['document_id'] ?? ''),
            'workspace_id' => (string) ($data['workspace_id'] ?? ''),
            'chunk_id' => (string) ($data['chunk_id'] ?? ''),
            'content' => trim((string) ($data['content'] ?? '')),
            'embedding' => array_values(array_map('floatval', $data['embedding'] ?? [])),
            'metadata' => is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            'created_at' => now()->toISOString()
        ];
    }

    protected function recordId(array $data): string
    {
        return md5(($data['document_id'] ?? '') . '_' . ($data['chunk_id'] ?? '') . '_vector');
    }

    protected function format(mixed $response): array
    {
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
