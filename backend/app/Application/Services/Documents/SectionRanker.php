<?php

namespace App\Application\Services\Documents;

class SectionRanker
{
    public function rank(array $chunks, array $sectionPlan): array
    {
        $weights = $sectionPlan['sections'] ?? $sectionPlan;

        foreach ($chunks as &$chunk) {
            $section = strtolower(trim($chunk['metadata']['section'] ?? ''));
            $normalized = $this->normalizeSection($section);

            $plannerScore = (float) ($weights[$normalized] ?? $weights[$section] ?? 0);
            $plannerScore = $plannerScore / 100;

            $semanticScore = (float) ($chunk['score'] ?? 0);
            $importanceBonus = $this->importanceScore($chunk['metadata']['importance'] ?? '');

            $chunk['planner_score'] = ($semanticScore * 0.70) + ($plannerScore * 0.25) + ($importanceBonus * 0.05);
            $chunk['metadata']['normalized_section'] = $normalized;
        }
        unset($chunk);

        usort($chunks, function ($a, $b) {
            return ($b['planner_score'] ?? 0) <=> ($a['planner_score'] ?? 0);
        });

        return $this->diversifySections($chunks);
    }

    protected function normalizeSection(string $section): string
    {
        $section = strtolower(trim($section));

        return match (true) {
            str_contains($section, 'method') || str_contains($section, 'روش') => 'methodology',
            str_contains($section, 'model') || str_contains($section, 'معماری') || str_contains($section, 'مدل') => 'architecture',
            str_contains($section, 'experiment') || str_contains($section, 'آزمایش') => 'experiments',
            str_contains($section, 'evaluation') || str_contains($section, 'ارزیابی') => 'evaluation',
            str_contains($section, 'intro') || str_contains($section, 'مقدمه') => 'introduction',
            str_contains($section, 'abstract') || str_contains($section, 'چکیده') => 'abstract',
            str_contains($section, 'result') || str_contains($section, 'نتایج') || str_contains($section, 'نتیجه') => 'results',
            str_contains($section, 'conclusion') || str_contains($section, 'نتیجه گیری') => 'conclusion',
            default => $section
        };
    }

    protected function importanceScore(string $importance): float
    {
        return match (strtolower($importance)) {
            'very_high' => 1.0,
            'high' => 0.7,
            'medium' => 0.4,
            'low' => 0.1,
            default => 0.3
        };
    }

    protected function diversifySections(array $chunks): array
    {
        $count = [];
        $result = [];

        foreach ($chunks as $chunk) {
            $section = $chunk['metadata']['normalized_section'] ?? 'general';
            $count[$section] = $count[$section] ?? 0;

            if ($count[$section] >= 3) {
                continue;
            }

            $result[] = $chunk;
            $count[$section]++;
        }

        return $result;
    }
}
