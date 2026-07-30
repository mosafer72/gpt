<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;

class DocumentChunkRepository
{
    protected string $table = 'document_chunk';

    public function store(array $data): array
    {
        $id = $this->recordId(
            (string) $data['document_id'],
            (int) $data['chunk_index']
        );

        return $this->extract(
            Surreal::query(
                new BoundQuery(
                    query: "
                    UPSERT type::record('document_chunk', \$id)
                    SET
                        document_id=<string>\$document_id,
                        workspace_id=<string>\$workspace_id,
                        chunk_index=<int>\$chunk_index,
                        content=<string>\$content,
                        tokens=<int>\$tokens,
                        metadata=\$metadata,
                        updated_at=time::now(),
                        created_at=IF created_at IS NONE THEN time::now() ELSE created_at END;

                    RETURN AFTER;
                    ",
                    bindings: [
                        'id' => $id,
                        'document_id' => (string) $data['document_id'],
                        'workspace_id' => (string) ($data['workspace_id'] ?? ''),
                        'chunk_index' => (int) $data['chunk_index'],
                        'content' => trim((string) ($data['content'] ?? '')),
                        'tokens' => (int) ($data['tokens'] ?? 0),
                        'metadata' => $data['metadata'] ?? []
                    ]
                )
            )
        )[0] ?? [];
    }

    public function findByDocument(
        string $documentId
    ): array {
        return $this->extract(
            Surreal::query(
                new BoundQuery(
                    query: "
                    SELECT *
                    FROM {$this->table}
                    WHERE document_id = \$id
                    ORDER BY chunk_index ASC;
                    ",
                    bindings: [
                        'id' => (string) $documentId
                    ]
                )
            )
        );
    }

    public function findByDocumentAndSection(
        string $documentId,
        string $section
    ): array {
        return $this->extract(
            Surreal::query(
                new BoundQuery(
                    query: "
                    SELECT *
                    FROM {$this->table}
                    WHERE
                        document_id = \$id
                        AND metadata.section = \$section
                    ORDER BY chunk_index ASC;
                    ",
                    bindings: [
                        'id' => (string) $documentId,
                        'section' => strtolower(trim($section))
                    ]
                )
            )
        );
    }

    public function findSections(
        string $documentId,
        array $sections
    ): array {
        if (empty($sections)) {
            return [];
        }

        return $this->extract(
            Surreal::query(
                new BoundQuery(
                    query: "
                    SELECT *
                    FROM {$this->table}
                    WHERE
                        document_id = \$id
                        AND metadata.section IN \$sections
                    ORDER BY chunk_index ASC;
                    ",
                    bindings: [
                        'id' => (string) $documentId,
                        'sections' => array_map(
                            fn($x) => strtolower(trim($x)),
                            $sections
                        )
                    ]
                )
            )
        );
    }

    public function searchContent(
        string $workspaceId,
        string $keyword,
        int $limit = 10
    ): array {
        if (blank($keyword)) {
            return [];
        }

        return $this->extract(
            Surreal::query(
                new BoundQuery(
                    query: "
                    SELECT *
                    FROM {$this->table}
                    WHERE
                        workspace_id = \$workspace_id
                        AND string::lowercase(content)
                        CONTAINS string::lowercase(\$keyword)
                    ORDER BY chunk_index ASC
                    LIMIT \$limit;
                    ",
                    bindings: [
                        'workspace_id' => (string) $workspaceId,
                        'keyword' => $keyword,
                        'limit' => $limit
                    ]
                )
            )
        );
    }

    public function searchByMetadata(
        string $workspaceId,
        string $key,
        string $value,
        int $limit = 10
    ): array {
        return $this->extract(
            Surreal::query(
                new BoundQuery(
                    query: "
                    SELECT *
                    FROM {$this->table}
                    WHERE
                        workspace_id = \$workspace_id
                        AND metadata.\$key = \$value
                    ORDER BY chunk_index ASC
                    LIMIT \$limit;
                    ",
                    bindings: [
                        'workspace_id' => (string) $workspaceId,
                        'key' => $key,
                        'value' => $value,
                        'limit' => $limit
                    ]
                )
            )
        );
    }

    public function deleteDocument(
        string $documentId
    ): mixed {
        return Surreal::query(
            new BoundQuery(
                query: "
                DELETE FROM {$this->table}
                WHERE document_id = \$id;
                ",
                bindings: [
                    'id' => (string) $documentId
                ]
            )
        );
    }

    protected function recordId(
        string $documentId,
        int $index
    ): string {
        return md5($documentId . '_' . $index);
    }

    protected function extract(mixed $response): array
    {
        if (empty($response)) {
            return [];
        }

        if (is_object($response) && method_exists($response, 'getData')) {
            $response = $response->getData();
        }

        if (isset($response[0]) && is_array($response[0])) {
            if (isset($response[0]['result']) && is_array($response[0]['result'])) {
                return collect($response[0]['result'])->values()->all();
            }

            return collect($response[0])->values()->all();
        }

        return is_array($response) ? collect($response)->values()->all() : [];
    }
}
