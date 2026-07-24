<?php

namespace App\Application\Services\Documents;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PdfExtractor
{
    public function extract(string $path): string
    {
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("File not found at: {$fullPath}");
        }

        // اجرای pdftotext سیستم‌عامل با انکودینگ UTF-8
        $process = new Process(['pdftotext', '-enc', 'UTF-8', $fullPath, '-']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
