<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ExtractDocumentTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Document $document
    ) {
    }

    public function handle(): void
    {
        // ۱. دریافت مسیر فایل (پشتیبانی از path یا file_path)
        $relativePath = $this->document->path ?? $this->document->file_path;

        if (!$relativePath) {
            Log::error("ExtractDocumentTextJob: Path is missing for Document ID {$this->document->id}");
            return;
        }

        // ۲. یافتن مسیر کامل فایل روی دیسک
        $filePath = Storage::disk($this->document->disk ?? 'public')->path($relativePath);

        if (!file_exists($filePath)) {
            Log::error("ExtractDocumentTextJob: File not found at {$filePath}");
            return;
        }

        $pythonPath = base_path('venv/bin/python3');
        $scriptPath = base_path('storage/scripts/extract_text.py');

        // اطمینان از وجود فایل پایتون در venv
        if (!file_exists($pythonPath)) {
            $pythonPath = 'python3';
        }

        $process = new Process([$pythonPath, $scriptPath, $filePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error("ExtractDocumentTextJob Python Error: " . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        $extractedText = trim($process->getOutput());

        if (empty($extractedText)) {
            Log::warning("ExtractDocumentTextJob: No text extracted from Document ID {$this->document->id}");
            return;
        }

        // ۳. ذخیره متن خام استخراج شده در جدول document_chunks
        DB::table('document_chunks')->insert([
            'document_id' => $this->document->id,
            'chunk_index' => 0,
            'content' => $extractedText,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->document->forceFill([
            'status' => 'completed'
        ])->save();
    }
}
