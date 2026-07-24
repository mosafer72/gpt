<?php

namespace App\Jobs;

use App\Application\Services\Ai\EmbeddingService;
use App\Application\Services\Documents\Chunker;
use App\Application\Services\Documents\PdfExtractor;
use App\Application\Services\Documents\SectionPlannerService;
use App\Infrastructure\Repositories\DocumentVectorRepository;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public Document $document
    ) {}

    public function handle(
        PdfExtractor $extractor,
        Chunker $chunker,
        EmbeddingService $embeddingService,
        DocumentVectorRepository $vectorRepository,
        SectionPlannerService $sectionPlanner
    ): void {

        ini_set('memory_limit', '512M');

        try {

            /*
            |--------------------------------------------------------------------------
            | Processing Status
            |--------------------------------------------------------------------------
            */

            $this->document->update([
                'status' => 'processing',
                'error'  => null
            ]);

            /*
            |--------------------------------------------------------------------------
            | Extract Text
            |--------------------------------------------------------------------------
            */

            $text = $extractor->extract($this->document->path);

            if (blank($text)) {
                throw new \Exception('Document text empty');
            }

            /*
            |--------------------------------------------------------------------------
            | Chunking
            |--------------------------------------------------------------------------
            */

            $chunks = $chunker->split($text);

            if (empty($chunks)) {
                throw new \Exception('No chunks generated');
            }

            unset($text);

            /*
            |--------------------------------------------------------------------------
            | Section Planning & Probability Map Initialization
            |--------------------------------------------------------------------------
            */

            $normalizeSection = function ($value): string {
                return strtolower(
                    preg_replace(
                        '/[^a-z0-9]/',
                        '',
                        trim((string) $value)
                    )
                );
            };

            $probabilityMap = [];

            try {
                $sectionPlans = $sectionPlanner->analyze(
                    documentId: (string) $this->document->id,
                    workspaceId: (string) $this->document->workspace_id,
                    sections: $chunks
                );

                if (is_array($sectionPlans)) {
                    foreach ($sectionPlans as $plan) {
                        if (!isset($plan['section'])) {
                            continue;
                        }

                        $probabilityMap[
                            $normalizeSection($plan['section'])
                        ] = (float) ($plan['answer_probability'] ?? 0);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Section planning fallback executed', [
                    'document_id' => $this->document->id,
                    'error'       => $e->getMessage()
                ]);
            }

            Log::info(
                'Section planning finished',
                [
                    'document_id' => $this->document->id,
                    'sections'    => count($probabilityMap)
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Helper: Safe String Conversion
            |--------------------------------------------------------------------------
            */

            $toSafeString = function ($value): string {
                if (is_array($value) || is_object($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
                }
                return trim((string) $value);
            };

            /*
            |--------------------------------------------------------------------------
            | Academic Metadata
            |--------------------------------------------------------------------------
            */

            $paperMetadata = [
                'title'    => $this->document->name ?? '',
                'abstract' => '',
                'authors'  => [],
                'year'     => '',
            ];

            /*
            |--------------------------------------------------------------------------
            | Embedding & Vector Storage
            |--------------------------------------------------------------------------
            */

            $batchSize = 20;

            foreach (array_chunk($chunks, $batchSize) as $batchIndex => $batch) {

                $vectors = [];

                foreach ($batch as $chunkIndex => $chunk) {

                    $content      = $toSafeString($chunk['content'] ?? '');
                    $sectionTitle = $toSafeString($chunk['section_title'] ?? '');
                    $section      = strtolower($toSafeString($chunk['section'] ?? ''));
                    $headingPath  = $toSafeString($chunk['heading_path'] ?? '');

                    if ($content === '') {
                        Log::warning(
                            'INVALID CHUNK CONTENT - SKIPPED',
                            ['document_id' => $this->document->id, 'chunk' => $chunk]
                        );
                        continue;
                    }

                    $sectionKey = $normalizeSection($section);
                    $sectionProbability = (float) ($probabilityMap[$sectionKey] ?? 0);

                    /*
                    |--------------------------------------------------------------------------
                    | Embedding Context
                    |--------------------------------------------------------------------------
                    */

                    $embeddingText =
                        "Document Type:\nAcademic Paper\n\n"
                        . "Section:\n{$section}\n\n"
                        . "Section Importance:\n{$sectionProbability}\n\n"
                        . "Section Title:\n{$sectionTitle}\n\n"
                        . "Content:\n{$content}";

                    try {
                        $embedding = $embeddingService->embedDocument($embeddingText);
                    } catch (\Throwable $e) {
                        Log::warning(
                            'Embedding failed for chunk',
                            [
                                'document_id' => $this->document->id,
                                'chunk_index' => $chunk['chunk_index'] ?? $chunkIndex,
                                'error'       => $e->getMessage()
                            ]
                        );
                        continue;
                    }

                    if (empty($embedding)) {
                        continue;
                    }

                    $vectors[] = [
                        'document_id'  => (string) $this->document->id,
                        'workspace_id' => (string) $this->document->workspace_id,
                        'chunk_id'     => uniqid('chunk_'),

                        'content'      => $content,
                        'embedding'    => $embedding,

                        'metadata'     => [
                            'filename'            => (string) $this->document->name,
                            'type'                => 'academic_paper',
                            'title'               => $toSafeString($paperMetadata['title'] ?? ''),
                            'abstract'            => $toSafeString($paperMetadata['abstract'] ?? ''),
                            'authors'             => is_array($paperMetadata['authors'] ?? null) ? $paperMetadata['authors'] : [],
                            'year'                => $toSafeString($paperMetadata['year'] ?? ''),
                            'section'             => $section,
                            'section_probability' => $sectionProbability,
                            'section_title'       => $sectionTitle,
                            'heading_path'        => $headingPath,
                            'importance'          => (int) ($chunk['importance'] ?? 0),
                            'chunk_index'         => (int) ($chunk['chunk_index'] ?? 0),
                            'section_chunk_index' => (int) ($chunk['section_chunk_index'] ?? 0),
                            'word_count'          => (int) ($chunk['word_count'] ?? 0),
                            'tokens'              => (int) (($chunk['word_count'] ?? 0) * 1.3),
                        ]
                    ];
                }

                if (!empty($vectors)) {
                    $vectorRepository->storeBulk($vectors);
                }

                unset($vectors);
                gc_collect_cycles();
            }

            /*
            |--------------------------------------------------------------------------
            | Complete
            |--------------------------------------------------------------------------
            */

            $this->document->update([
                'status' => 'completed'
            ]);

            Log::info(
                'Academic document processed successfully',
                [
                    'document_id' => $this->document->id,
                    'chunks'      => count($chunks)
                ]
            );

        } catch (\Throwable $e) {

            Log::error(
                'Academic processing failed',
                [
                    'document_id' => $this->document->id,
                    'error'       => $e->getMessage()
                ]
            );

            $this->document->update([
                'status' => 'failed',
                'error'  => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
