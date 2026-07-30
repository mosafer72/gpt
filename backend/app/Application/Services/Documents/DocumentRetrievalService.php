<?php

namespace App\Application\Services\Documents;

use App\Application\Services\Ai\EmbeddingService;
use App\Domain\Documents\Contracts\DocumentVectorRepositoryInterface;
use App\Application\DTOs\Documents\RetrievalResult;
use Illuminate\Support\Facades\Log;

class DocumentRetrievalService
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected DocumentVectorRepositoryInterface $vectorRepository
    ) {}

    public function retrieve(
        string $workspaceId,
        string $question,
        int $limit = 8,
        array $filters = []
    ): array {

        try {
            // ✅ اصلاح شد: استفاده از embedQuery به جای embedDocument
            $embedding = $this->embeddingService->embedQuery($question);
        } catch (\Throwable $e) {
            return [];
        }

        if (empty($embedding)) {
            return [];
        }

        $results = $this->vectorRepository->search(
            workspaceId: $workspaceId,
            vector: $embedding,
            limit: $limit * 4,
            filters: $filters
        );

        if (empty($results)) {
            return [];
        }

        $ranked = [];

        foreach ($results as $item) {
            $metadata = $item['metadata'] ?? [];
            $score = (float) ($item['score'] ?? 0);
            $section = strtolower($metadata['section'] ?? '');

            // پشتیبانی از بخش‌های مختلف علمی
            if (in_array($section, ['method', 'methods', 'methodology', 'architecture', 'model', 'implementation', 'روش'], true)) {
                $score += 0.07;
            }

            if (in_array($section, ['experiment', 'evaluation', 'results', 'conclusion', 'abstract', 'چکیده'], true)) {
                $score += 0.05;
            }

            $importance = $metadata['importance'] ?? '';
            if ($importance === 'very_high') {
                $score += 0.05;
            } elseif ($importance === 'high') {
                $score += 0.03;
            }

            if (!empty($metadata['title'])) {
                $score += 0.01;
            }

            $ranked[] = new RetrievalResult(
                chunkId: (string) ($item['chunk_id'] ?? ''),
                content: $item['content'] ?? '',
                score: min($score, 1.0),
                metadata: $metadata
            );
        }

        usort($ranked, fn($a, $b) => $b->score <=> $a->score);

        return array_values(array_slice($ranked, 0, $limit));
    }
}
