<?php

namespace App\Application\Services\Documents;

use App\Application\Services\Ai\EmbeddingService;
use App\Domain\Contracts\SmallChatProvider;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Infrastructure\Repositories\DocumentSectionTranslationRepository;
use Illuminate\Support\Facades\Log;

class SectionTranslationService
{
    public function __construct(
        protected SmallChatProvider $llm,
        protected EmbeddingService $embedding,
        protected DocumentSectionTranslationRepository $repository
    ) {
    }

    public function translate(
        array $section,
        string $documentId,
        string $workspaceId
    ): bool {
        $content = trim($section['content'] ?? '');

        if (blank($content)) {
            return false;
        }

        try {
            $response = $this->llm->chat(new ChatRequest(
                messages: [
                    new ChatMessage(
                        role: 'user',
                        content:
                        "Translate this scientific section to Persian.
CRITICAL: Return ONLY valid JSON. Do NOT insert literal line breaks inside string values (use \\n for line breaks instead).

Format:
{
\"title\":\"\",
\"translation\":\"\",
\"summary\":\"\",
\"keywords\":[]
}

Rules:
- Academic Persian
- Keep formulas
- Keep technical terms

TEXT:
" . $content
                    )
                ],
                model: 'qwen2.5-64k:latest',
                temperature: 0.1,
                maxTokens: 4000
            ));

            $raw = $response->content ?? '';

            $json = $this->parseJson($raw);

            if (!$json) {
                \Log::warning(
                    'TRANSLATION JSON FAILED',
                    [
                        'document_id' => $documentId,
                        'output' => $raw
                    ]
                );

                return false;
            }

            $translation = trim($json['translation'] ?? '');

            if (blank($translation)) {
                \Log::warning(
                    'EMPTY TRANSLATION',
                    [
                        'document_id' => $documentId
                    ]
                );

                return false;
            }

            try {
                $vector = $this->embedding->embedDocument($translation);
            } catch (\Throwable $e) {
                \Log::error(
                    'TRANSLATION EMBEDDING FAILED',
                    [
                        'document_id' => $documentId,
                        'error' => $e->getMessage()
                    ]
                );

                $vector = [];
            }

            $this->repository->store([
                'document_id' => $documentId,
                'workspace_id' => $workspaceId,
                'section' => $section['section'] ?? 'general',
                'title' => $json['title'] ?? 'Document',
                'original_content' => $content,
                'translated_content' => $translation,
                'summary_fa' => $json['summary'] ?? '',
                'keywords' => is_array($json['keywords'] ?? null)
                    ? $json['keywords']
                    : [],
                'embedding' => $vector,
                'metadata' => [
                    'order' => (int) ($section['order'] ?? 0),
                    'importance' => $section['importance'] ?? 'medium',
                    'translated_by' => 'qwen2.5-64k:latest'
                ]
            ]);



            return true;

        } catch (\Throwable $e) {
            \Log::error(
                'SECTION TRANSLATION ERROR',
                [
                    'document_id' => $documentId,
                    'error' => $e->getMessage()
                ]
            );

            return false;
        }
    }

    protected function parseJson(string $content): ?array
    {
        $clean = trim($content);

        // ۱. استخراج بلاک JSON در صورتی که داخل مارک‌داون قرار گرفته باشد
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/u', $clean, $matches)) {
            $clean = trim($matches[1]);
        } elseif (preg_match('/\{[\s\S]*\}/u', $clean, $matches)) {
            $clean = trim($matches[0]);
        }

        $clean = preg_replace('/^```(?:json)?|```$/i', '', $clean);
        $clean = trim($clean);

        // تلاش ۱: پارس مستقیم استاندارد
        $json = json_decode($clean, true);
        if (is_array($json) && json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // تلاش ۲: اصلاح خطوط جدید غیرمجاز و Tabها درون رشته‌های JSON
        $sanitized = preg_replace_callback('/"(?:\\\\.|[^"\\\\])*"/s', function ($m) {
            return str_replace(
                ["\r\n", "\n", "\r", "\t"],
                ["\\n", "\\n", "\\n", "\\t"],
                $m[0]
            );
        }, $clean);

        $json = json_decode($sanitized, true);
        if (is_array($json) && json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // تلاش ۳: حذف کاراکترهای غیرقابل چاپ ASCII
        $sanitizedControl = preg_replace('/[\x00-\x1F\x7F]/u', '', $sanitized);
        $json = json_decode($sanitizedControl, true);
        if (is_array($json) && json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return null;
    }
}
