<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;

class DocumentSectionPlanRepository
{
    protected string $table = 'document_section_plan';

    public function storeBulk(array $items): array
    {
        $results = [];

        foreach ($items as $item) {

            $payload = [
                'document_id' => (string) ($item['document_id'] ?? ''),
                'workspace_id' => (string) ($item['workspace_id'] ?? ''),
                'section' => strtolower(trim($item['section'] ?? '')),
                'section_title' => trim($item['section_title'] ?? ''),
                'summary' => (string) ($item['summary'] ?? ''),
                'keywords' => is_array($item['keywords'] ?? null)
                    ? array_values($item['keywords'])
                    : [],
                'question_types' => is_array($item['question_types'] ?? null)
                    ? array_values($item['question_types'])
                    : [],
                'answer_probability' => (float) ($item['answer_probability'] ?? 0.8),
                'planner_rank' => (int) ($item['planner_rank'] ?? 0),
                'selected_count' => (int) ($item['selected_count'] ?? 0),

                // SurrealDB object
                'metadata' => is_array($item['metadata'] ?? null)
                    ? (object) $item['metadata']
                    : (object) [],

                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ];

            $results[] = Surreal::query(
                new BoundQuery(
                    query: "
                        CREATE {$this->table}
                        CONTENT \$payload;
                    ",
                    bindings: [
                        'payload' => $payload
                    ]
                )
            );
        }

        return $results;
    }


    public function findByDocument(string $documentId): array
    {
        $response = Surreal::query(
            new BoundQuery(
                query: "
                    SELECT *
                    FROM {$this->table}
                    WHERE document_id = \$documentId
                    ORDER BY planner_rank ASC;
                ",
                bindings: [
                    'documentId' => (string) $documentId
                ]
            )
        );

        return $this->format($response);
    }


    public function findByDocumentAndSection(
        string $documentId,
        string $section
    ): array {

        $response = Surreal::query(
            new BoundQuery(
                query: "
                    SELECT *
                    FROM {$this->table}
                    WHERE document_id = \$documentId
                    AND section = \$section;
                ",
                bindings: [
                    'documentId' => (string) $documentId,
                    'section' => strtolower(trim($section))
                ]
            )
        );

        return $this->format($response);
    }


    protected function format($response): array
    {
        if (
            is_object($response)
            &&
            method_exists($response, 'getData')
        ) {
            $data = $response->getData();

            return $data[0]['result'] ?? [];
        }

        return [];
    }
}
