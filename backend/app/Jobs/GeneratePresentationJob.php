<?php

namespace App\Jobs;

use App\Application\Context\ChatContext;
use App\Application\Pipelines\Presentation\PresentationWorkspacePipeline;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeneratePresentationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document
    ) {}

    public function handle(
        PresentationWorkspacePipeline $pipeline
    ): void {
        Log::info('Presentation Job Started', [
            'document_id' => $this->document->id
        ]);

        $metadata = $this->document->metadata;

        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        if (
            in_array(
                $metadata['generation_state'] ?? null,
                ['running', 'completed']
            )
        ) {
            Log::info('Presentation already processed', [
                'state' => $metadata['generation_state']
            ]);
            return;
        }

        try {
            $metadata['generation_state'] = 'running';

            $this->document->update([
                'status' => 'processing',
                'metadata' => $metadata
            ]);

            $message = $metadata['message'] ?? '';

            if (!$message) {
                throw new \Exception("Presentation message empty");
            }

            Log::info('Creating ChatContext', [
                'message_length' => strlen($message)
            ]);

            $context = new ChatContext(
                userId: (string) $this->document->user_id,
                conversationId: null,
                currentMessage: $message,
            );

            $context->setWorkspace($this->document->workspace);

            Log::info('Workspace Loaded', [
                'workspace_id' => $context->workspace->id
            ]);

            $model = $context
                ->workspace
                ->plan
                ->models()
                ->where('is_active', true)
                ->where('type', 'presentation')
                ->first();

            if (!$model) {
                throw new \Exception("Presentation model not found");
            }

            Log::info('Presentation Model Selected', [
                'model_id' => $model->id,
                'model_key' => $model->key
            ]);

            $context->model = $model->key;

            $context->addMetadata('presentation', $metadata);

            Log::info('Running Presentation Pipeline');

            $pipeline->handle($context);

            Log::info('Pipeline Finished');

            /*
             |--------------------------------------------------------------------------
             | دریافت HTML خروجی مدل و اعتبارسنچی
             |--------------------------------------------------------------------------
             */

            $html = $context->getMetadata('presentation_html');

            Log::info('Generated HTML Received', [
                'exists' => !empty($html),
                'length' => is_string($html) ? strlen($html) : 0
            ]);

            $isValid = !empty($html) && (
                str_contains($html, '<scientific-document>') ||
                str_contains($html, '<div class="scientific-document">')
            );

            if (!$isValid) {
                Log::error('Invalid Presentation Structure', [
                    'preview' => mb_substr((string) $html, 0, 1000)
                ]);
                throw new \Exception("Invalid presentation structure");
            }

            /*
             |--------------------------------------------------------------------------
             | ارسال HTML به Renderer و مدیریت هوشمند خروجی فایل
             |--------------------------------------------------------------------------
             */

            Log::info('Sending HTML To PPT Renderer');

            $response = Http::timeout(2300)
                ->post("http://127.0.0.1:3001/generate", [
                    'html' => $html
                ]);

            Log::info('Renderer Response Status', [
                'status' => $response->status()
            ]);

            if (!$response->successful()) {
                throw new \Exception("PPT service error: " . $response->body());
            }

            $result = $response->json();
            $file = $result['file'] ?? null;

            // روش جایگزین اول: استخراج نام فایل از بدنه پاسخ در صورت وجود تداخل لاگ
            if (!$file) {
                $body = $response->body();
                if (preg_match('/presentation_\d+\.pptx/', $body, $matches)) {
                    $file = $matches[0];
                }
            }

            // روش جایگزین دوم: اسکن پوشه خروجی برای شناسایی آخرین فایل `.pptx` ساخته‌شده
            if (!$file) {
                $outputDir = base_path("presentation-service/output");
                if (is_dir($outputDir)) {
                    $files = glob($outputDir . '/presentation_*.pptx');
                    if (!empty($files)) {
                        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                        $file = basename($files[0]);
                    }
                }
            }

            if (!$file) {
                throw new \Exception("PPT file missing from response");
            }

            Log::info('PPT Generated Successfully', ['file' => $file]);

            $source = base_path("presentation-service/output/" . $file);

            if (!file_exists($source)) {
                throw new \Exception("Generated PPT source file missing at: " . $source);
            }

            $relative = "presentations/" . $this->document->id . ".pptx";
            $destination = storage_path("app/public/" . $relative);

            if (!is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0777, true);
            }

            copy($source, $destination);

            $this->document->update([
                "path" => $relative,
                "size" => filesize($destination),
                "status" => "completed",
                "metadata" => array_merge(
                    $metadata,
                    [
                        "generation_state" => "completed",
                        "renderer_file" => $file
                    ]
                )
            ]);

            Log::info('Presentation Completed Successfully', [
                'document_id' => $this->document->id,
                'path' => $relative
            ]);

        } catch (\Throwable $e) {
            Log::error('Presentation Job Failed', [
                'document_id' => $this->document->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
