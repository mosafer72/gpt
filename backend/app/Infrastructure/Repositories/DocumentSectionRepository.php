<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;

class DocumentSectionRepository
{
    protected string $table = 'document_section_plan';


    public function storeBulk(array $items): array
    {
        $results = [];

        foreach ($items as $item) {

            $results[] = Surreal::query(
                new BoundQuery(
                    query: "
                        CREATE {$this->table}
                        CONTENT \$payload;
                    ",
                    bindings: [
                        'payload' => [
                            'document_id' => (string) $item['document_id'],
                            'workspace_id' => (string) $item['workspace_id'],
                            'section' => strtolower(trim($item['section'] ?? '')),
                            'section_title' => trim($item['section_title'] ?? ''),
                            'summary' => $item['summary'] ?? '',
                            'keywords' => $item['keywords'] ?? [],
                            'question_types' => $item['question_types'] ?? [],
                            'answer_probability' => $item['answer_probability'] ?? 0.8,
                            'planner_rank' => $item['planner_rank'] ?? 0,
                            'selected_count' => $item['selected_count'] ?? 0,
                            'metadata' => $item['metadata'] ?? [],
                            'created_at' => now()->toISOString()
                        ]
                    ]
                )
            );
        }

        return $results;
    }


    public function findByDocument(
        string $documentId
    ): array {

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
