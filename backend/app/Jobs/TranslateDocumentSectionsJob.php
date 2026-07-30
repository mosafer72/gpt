<?php

namespace App\Jobs;

use App\Models\Document;
use App\Infrastructure\Repositories\DocumentChunkRepository;
use App\Application\Services\Documents\SectionTranslationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TranslateDocumentSectionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(public Document $document)
    {
    }

    public function handle(
        DocumentChunkRepository $chunkRepository,
        SectionTranslationService $translator
    ): void {

        $success = 0;
        $skipped = 0;
        $failed = 0;

        try {

            $this->document->refresh();

            $this->document->update([
                'translation_status' => 'processing'
            ]);

            $chunks = $chunkRepository->findByDocument(
                (string) $this->document->id
            );


            if (!$chunks) {
                throw new \Exception('No document chunks found');
            }


            foreach ($chunks as $chunk) {

                $metadata = $chunk['metadata'] ?? [];

                $content = trim(
                    (string) ($chunk['content'] ?? '')
                );

                if (blank($content)) {

                    $skipped++;
                    continue;
                }


                if (($metadata['section'] ?? '') === 'references') {

                    $skipped++;
                    continue;
                }


                try {


                    $result = $translator->translate(

                        section: [

                            'content' => $content,

                            'section' =>
                                $metadata['section'] ?? 'general',

                            'title' =>
                                $metadata['section_title'] ?? 'Document',

                            'word_count' =>
                                $metadata['word_count']
                                ??
                                $this->wordCount($content),

                            'order' =>
                                (int) ($chunk['chunk_index'] ?? 0),

                            'importance' =>
                                $metadata['importance'] ?? 'medium'

                        ],

                        documentId: (string) $this->document->id,

                        workspaceId: (string) $this->document->workspace_id

                    );


                    if ($result) {

                        $success++;

                    } else {

                        $failed++;

                    }


                } catch (\Throwable $e) {

                    $failed++;

                    Log::warning(
                        'SECTION TRANSLATION FAILED',
                        [
                            'document_id' => $this->document->id,
                            'chunk_id' => $chunk['id'] ?? null,
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }


            // اگر هیچ ترجمه موفقی نداشتیم و حداقل یک ترجمه ناموفق داشتیم، یعنی خطا رخ داده است.
// اما اگر موفقیت صفر بود و فقط skip داشتیم، یعنی داکیومنت نیاز به ترجمه نداشته است و نباید خطا بدهد.
            if ($success === 0 && $failed > 0) {
                throw new \Exception(
                    "Translation failed. success:$success skipped:$skipped failed:$failed"
                );
            }

            // (اختیاری) اگر می‌خواهید حالتی که هیچ فایلی برای ترجمه نبوده را هم بررسی کنید:
            if ($success === 0 && $skipped > 0 && $failed === 0) {
                Log::info('NO TRANSLATION NEEDED (All chunks were skipped)', [
                    'document_id' => $this->document->id,
                    'skipped' => $skipped
                ]);
            }


            $this->document->update([
                'translation_status' => 'completed'
            ]);



        } catch (\Throwable $e) {

            Log::error(
                'DOCUMENT SECTION TRANSLATION FAILED',
                [
                    'document_id' => $this->document->id,
                    'error' => $e->getMessage()
                ]
            );


            $this->document->update([
                'translation_status' => 'failed'
            ]);

            throw $e;
        }
    }


    protected function wordCount(string $text): int
    {
        return blank($text)
            ? 0
            : count(
                preg_split('/\s+/u', trim($text))
            );
    }
}
