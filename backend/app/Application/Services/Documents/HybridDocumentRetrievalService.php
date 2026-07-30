<?php

namespace App\Application\Services\Documents;

use App\Application\Services\Ai\EmbeddingService;
use App\Application\DTOs\Documents\RetrievalResult;
use App\Domain\Documents\Contracts\DocumentVectorRepositoryInterface;
use App\Infrastructure\Repositories\DocumentChunkRepository;
use Illuminate\Support\Facades\Log;

class HybridDocumentRetrievalService
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected DocumentVectorRepositoryInterface $vectorRepository,
        protected DocumentChunkRepository $chunkRepository,
        protected SectionRanker $sectionRanker
    ) {}

    public function retrieve(
        string $workspaceId,
        string $question,
        int $limit = 8,
        array $filters = [],
        array $sectionPlan = []
    ): array {
        $semanticResults = [];

        // ۱. جستجوی برداری (Vector Search)
        try {
            $embedding = $this->embeddingService->embedQuery($question);

            if (!empty($embedding)) {
                $semanticResults = $this->vectorRepository->search(
                    workspaceId: $workspaceId,
                    vector: $embedding,
                    limit: $limit * 4,
                    filters: $filters
                );
            }
        } catch (\Throwable $e) {
            Log::warning('HYBRID VECTOR SEARCH FAILED', [
                'error' => $e->getMessage()
            ]);
        }

        // ۲. استخراج کلمات کلیدی برای جلوگیری از جستجوی جملات محاوره‌ای
        $cleanKeyword = $this->extractSearchableKeywords($question);
        $keywordResults = [];

        if (!empty($cleanKeyword)) {
            $keywordResults = $this->chunkRepository->searchContent(
                workspaceId: $workspaceId,
                keyword: $cleanKeyword,
                limit: $limit * 2
            );
        }

        // ۳. ادغام نتایج Semantic و Keyword
        $merged = [];

        foreach ($semanticResults as $item) {
            $id = $item['chunk_id'] ?? md5($item['content'] ?? '');
            $merged[$id] = [
                'chunk_id' => $id,
                'content' => $item['content'] ?? '',
                'metadata' => $item['metadata'] ?? [],
                'score' => (float) ($item['score'] ?? 0),
                'vector_score' => (float) ($item['score'] ?? 0),
                'keyword_score' => 0
            ];
        }

        foreach ($keywordResults as $item) {
            $id = $item['id'] ?? md5($item['content'] ?? '');
            if (!isset($merged[$id])) {
                $merged[$id] = [
                    'chunk_id' => $id,
                    'content' => $item['content'] ?? '',
                    'metadata' => $item['metadata'] ?? [],
                    'score' => 0.5,
                    'vector_score' => 0,
                    'keyword_score' => 0.8
                ];
            } else {
                $merged[$id]['keyword_score'] = 0.8;
                // ترکیب وزن در صورت وجود در هر دو جستجو
                $merged[$id]['score'] = ($merged[$id]['vector_score'] * 0.7) + (0.8 * 0.3);
            }
        }

        if (empty($merged)) {
            return [];
        }

        // ۴. رتبه‌بندی نهایی بر اساس Plan بخش‌ها توسط SectionRanker
        $chunksArray = array_values($merged);

        if (!empty($sectionPlan)) {
            $chunksArray = $this->sectionRanker->rank($chunksArray, $sectionPlan);
        }

        // ۵. تبدیل به DTO
        $results = [];
        foreach ($chunksArray as $item) {
            $results[] = new RetrievalResult(
                chunkId: (string) ($item['chunk_id'] ?? ''),
                content: $item['content'] ?? '',
                score: min((float) ($item['planner_score'] ?? $item['score'] ?? 0), 1.0),
                metadata: $item['metadata'] ?? []
            );
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * حذف عبارات محاوره‌ای و استخراج کلمات کلیدی اصلی برای Full-text Search
     */
    protected function extractSearchableKeywords(string $query): string
    {
        $stopWords = [
            'رو', 'به', 'بهم', 'بگو', 'چیه', 'چیست', 'عزیز', 'لطفا',
            'نمایش', 'بده', 'میخوام', 'درباره', 'مورد', 'توضیح', 'کن'
        ];

        $words = preg_split('/\s+/u', mb_strtolower(trim($query)));
        $filtered = array_filter($words, fn($w) => !in_array($w, $stopWords, true) && mb_strlen($w) > 2);

        return implode(' ', $filtered);
    }
}
