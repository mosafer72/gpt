<?php

namespace App\Jobs;

use App\Models\Document;
use App\Application\Services\Documents\{
    PdfExtractor,
    DocumentMetadataExtractor,
    DocumentStructureBuilder,
    Chunker,
    DocumentSectionDetector
};
use App\Application\Services\Ai\EmbeddingService;
use App\Infrastructure\Repositories\{
    DocumentChunkRepository,
    DocumentVectorRepository,
    DocumentSectionPlanRepository
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(public Document $document)
    {
    }

    public function handle(
        PdfExtractor $extractor,
        DocumentSectionDetector $sectionDetector,
        DocumentMetadataExtractor $metadataExtractor,
        DocumentStructureBuilder $structureBuilder,
        Chunker $chunker,
        EmbeddingService $embeddingService,
        DocumentChunkRepository $chunkRepository,
        DocumentVectorRepository $vectorRepository,
        DocumentSectionPlanRepository $sectionPlanRepository
    ): void {
        try {
            $this->document->update(['status' => 'processing', 'error' => null]);

            $text = $extractor->extract($this->document->path);

            if (blank($text))
                throw new \Exception('Empty document');

            $sections = $sectionDetector->detect($text);

            if (empty($sections))
                throw new \Exception('No sections detected');

            $metadata = $metadataExtractor->extract($text, $this->document->name);

            $this->document->update(['metadata' => $metadata]);

            $structureBuilder->build(document: $this->document, sections: $sections);

            /*
            |--------------------------------------------------------------------------
            | ذخیره پلان بخش‌ها با لاگ‌گیری دقیق خطاهای احتمالی دیتابیس
            |--------------------------------------------------------------------------
            */
            try {
                $planSections = [];
                foreach ($sections as $index => $section) {
                    $sectionName = strtolower(trim($section['name'] ?? $section['title'] ?? 'general'));

                    $planSections[] = [
                        'document_id' => (string) $this->document->id,
                        'workspace_id' => (string) $this->document->workspace_id,
                        'section' => $sectionName,
                        'section_title' => $section['title'] ?? $sectionName,
                        'summary' => $section['summary'] ?? '',
                        'keywords' => $section['keywords'] ?? [],
                        'question_types' => $section['question_types'] ?? [],
                        'answer_probability' => (float) ($section['answer_probability'] ?? 0.8),
                        'planner_rank' => $index + 1,
                        'selected_count' => 0,
                        'metadata' => $section['metadata'] ?? []
                    ];
                }

                if (!empty($planSections)) {
                    Log::info('PLAN DATA', [
                        'data' => $planSections
                    ]);
                    $sectionPlanRepository->storeBulk($planSections);
                    Log::info('SECTION PLAN STORED SUCCESSFULLY', ['document_id' => $this->document->id, 'count' => count($planSections)]);
                }
            } catch (\Throwable $planEx) {
                Log::error('CRITICAL: SECTION PLAN STORE FAILED', [
                    'document_id' => $this->document->id,
                    'error_message' => $planEx->getMessage(),
                    'error_trace' => $planEx->getTraceAsString()
                ]);
                // اگر خواستید پردازش متوقف نشود، اینجا را throw نکنید؛ اما برای تست خطا می‌گذاریم بماند یا ثبت شود
            }

            $chunks = $chunker->split($sections);

            if (empty($chunks))
                throw new \Exception('No chunks');

            $chunkRepository->deleteDocument((string) $this->document->id);
            $vectorRepository->deleteDocument((string) $this->document->id);

            foreach ($chunks as $chunk) {
                $content = trim($chunk['content'] ?? '');

                if (blank($content))
                    continue;

                $chunkMetadata = [
                    'title' => $metadata['title'] ?? '',
                    'authors' => $metadata['authors'] ?? [],
                    'year' => $metadata['year'] ?? null,
                    'filename' => $this->document->name,
                    'type' => 'academic_paper',
                    'section' => $chunk['section'] ?? 'general',
                    'section_title' => $chunk['section_title'] ?? '',
                    'heading_path' => $chunk['heading_path'] ?? [],
                    'importance' => $chunk['importance'] ?? 'medium',
                    'word_count' => $chunk['word_count'] ?? 0
                ];

                $saved = $chunkRepository->store([
                    'document_id' => (string) $this->document->id,
                    'workspace_id' => (string) $this->document->workspace_id,
                    'chunk_index' => (int) ($chunk['chunk_index'] ?? 0),
                    'content' => $content,
                    'tokens' => (int) ($chunk['word_count'] ?? 0),
                    'metadata' => $chunkMetadata
                ]);

                $chunkId = $saved['id'] ?? null;

                if (!$chunkId)
                    continue;

                try {
                    $embedding = $embeddingService->embedDocument(
                        "Title: " . ($metadata['title'] ?? '') . "\nSection: " . ($chunk['section'] ?? '') . "\nContent: " . $content
                    );

                    if (!empty($embedding)) {
                        $vectorRepository->store([
                            'document_id' => (string) $this->document->id,
                            'workspace_id' => (string) $this->document->workspace_id,
                            'chunk_id' => (string) $chunkId,
                            'content' => $content,
                            'embedding' => $embedding,
                            'metadata' => $chunkMetadata
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Embedding failed', ['document_id' => $this->document->id, 'error' => $e->getMessage()]);
                }
            }

            $this->document->update(['status' => 'completed']);

            // TranslateDocumentSectionsJob::dispatch($this->document);

            Log::info('DOCUMENT PROCESSED', ['document_id' => $this->document->id, 'chunks' => count($chunks)]);

        } catch (\Throwable $e) {
            $this->document->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);

            Log::error('DOCUMENT PROCESS FAILED', ['document_id' => $this->document->id, 'error' => $e->getMessage()]);

            throw $e;
        }
    }
}
