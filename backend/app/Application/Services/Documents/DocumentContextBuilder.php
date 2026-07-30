<?php

namespace App\Application\Services\Documents;

use App\Application\DTOs\Documents\RetrievalResult;
use Illuminate\Support\Facades\Log;

class DocumentContextBuilder
{
    public function build(array $results, int $maxWords = 3000): array
    {
        if (empty($results)) {
            return [];
        }

        $chunks = collect($results)
            ->map(function ($item) {

                if ($item instanceof RetrievalResult) {
                    return method_exists($item, 'toArray')
                        ? $item->toArray()
                        : [
                            'content' => $item->content ?? '',
                            'score' => $item->score ?? 0,
                            'metadata' => $item->metadata ?? []
                        ];
                }

                return $item;
            })
            ->filter(function ($item) {

                if (!is_array($item)) {
                    return false;
                }

                if (empty(trim($item['content'] ?? ''))) {


                    return false;
                }

                return true;
            })
            ->map(function ($item) {

                $metadata = $item['metadata'] ?? [];

                return [
                    ...$item,
                    'metadata' => $metadata,
                    'title' => $metadata['title'] ?? 'Unknown Document',
                    'section' => strtolower(trim($metadata['section'] ?? 'general', ": ")),
                    'chunk_id' => $item['chunk_id'] ?? $metadata['chunk_id'] ?? null,
                    'score' => (float) (
                        $item['final_score']
                        ?? $item['planner_score']
                        ?? $item['score']
                        ?? 0
                    )
                ];
            })
            ->unique(function ($item) {
                return $item['chunk_id']
                    ?? md5(trim($item['content']));
            })
            ->sortByDesc('score')
            ->values();


        if ($chunks->isEmpty()) {
            return [];
        }


        $context = [];
        $wordCount = 0;


        foreach ($chunks as $chunk) {

            if ($wordCount >= $maxWords) {
                break;
            }


            $content = trim($chunk['content']);

            $words = $this->wordCount($content);


            if (($wordCount + $words) > $maxWords) {

                $content = $this->limitWords(
                    $content,
                    $maxWords - $wordCount
                );

                $words = $this->wordCount($content);
            }


            $context[] = [
                'chunk_id' => $chunk['chunk_id'],
                'title' => $chunk['title'],
                'section' => $chunk['section'],
                'content' => $content,
                'score' => $chunk['score'],
                'importance' => $this->sectionImportance($chunk['section']),
                'metadata' => [
                    'section_title' => $chunk['metadata']['section_title'] ?? '',
                    'chunk_index' => $chunk['metadata']['chunk_index'] ?? 0,
                    'word_count' => $words,
                    'original_metadata' => $chunk['metadata']
                ]
            ];


            $wordCount += $words;
        }


        if (empty($context)) {
            return [];
        }


        $result = [
            'document_info' => [
                'title' => $context[0]['title'] ?? null,
                'documents' => collect($context)
                    ->pluck('title')
                    ->unique()
                    ->values()
                    ->all()
            ],
            'sections' => $context,
            'total_words' => $wordCount
        ];


        $result['text'] = $this->toText($result);


        return $result;
    }


    public function toText(array $context): string
    {
        if (empty($context['sections'])) {
            return '';
        }

        $text = "Retrieved Document Context:\n\n";


        foreach ($context['sections'] as $index => $section) {

            $text .=
                "====================\n" .
                "Context Block " . ($index + 1) . "\n" .
                "Document: {$section['title']}\n" .
                "Section: " . ucfirst($section['section']) . "\n\n" .
                $section['content'] .
                "\n\n";
        }


        return trim($text);
    }


    protected function sectionImportance(string $section): string
    {
        return match (true) {

            str_contains($section, 'method')
            || str_contains($section, 'architecture')
            => 'very_high',

            str_contains($section, 'experiment')
            || str_contains($section, 'evaluation')
            || str_contains($section, 'result')
            || str_contains($section, 'conclusion')
            => 'high',

            str_contains($section, 'abstract')
            || str_contains($section, 'intro')
            => 'medium',

            default
            => 'normal'
        };
    }


    protected function wordCount(string $text): int
    {
        return $text === ''
            ? 0
            : count(preg_split('/\s+/u', trim($text)));
    }


    protected function limitWords(string $text, int $limit): string
    {
        return implode(
            ' ',
            array_slice(
                preg_split('/\s+/u', trim($text)),
                0,
                max(1, $limit)
            )
        );
    }
}
