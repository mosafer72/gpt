<?php

namespace App\Application\Research;

use Spatie\PdfToText\Pdf;

class PdfTextExtractor
{
    public function extract(string $path): string
    {
        return Pdf::getText($path);
    }
}
