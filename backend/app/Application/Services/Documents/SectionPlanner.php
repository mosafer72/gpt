<?php

namespace App\Application\Services\Documents;

use Illuminate\Support\Facades\Log;

class SectionPlanner
{
    public function plan(
        array $intent,
        array $sections = [],
        string $query = ''
    ): array {
        $type = strtolower(trim($intent['type'] ?? 'general'));

        if (empty($sections)) {
            return [
                'mode' => 'global',
                'sections' => []
            ];
        }

        $scores = [];

        foreach ($sections as $section) {

            $title = strtolower(
                trim(
                    $section['name']
                    ?? $section['normalized']
                    ?? $section['title']
                    ?? ''
                )
            );

            if ($title === '') {
                continue;
            }

            $score = 0;

            switch ($type) {

                case 'abstract':

                    if (
                        str_contains($title, 'abstract')
                        || str_contains($title, 'summary')
                        || str_contains($title, 'overview')
                    ) {
                        $score = 100;
                    }

                    break;


                case 'title':

                    if (
                        str_contains($title, 'header')
                        || str_contains($title, 'title')
                        || str_contains($title, 'abstract')
                    ) {
                        $score = 100;
                    }

                    break;


                case 'summary':

                    if (
                        str_contains($title, 'abstract')
                        || str_contains($title, 'introduction')
                        || str_contains($title, 'conclusion')
                    ) {
                        $score = 90;
                    }

                    break;


                case 'section_specific':

                    $score = $this->sectionMatchScore(
                        $query,
                        $title
                    );

                    break;


                case 'comparison':

                    if (
                        str_contains($title, 'result')
                        || str_contains($title, 'evaluation')
                        || str_contains($title, 'comparison')
                    ) {
                        $score = 90;
                    }

                    break;


                case 'explanation':

                    if (
                        str_contains($title, 'method')
                        || str_contains($title, 'model')
                        || str_contains($title, 'architecture')
                        || str_contains($title, 'approach')
                    ) {
                        $score = 85;
                    }

                    break;


                default:

                    $score = $this->generalScore($title);

                    break;
            }


            if ($score > 0) {
                $scores[$title] = $score;
            }
        }


        arsort($scores);


        if (empty($scores)) {

            return [
                'mode' => 'global',
                'sections' => []
            ];
        }


        return [
            'mode' => 'section',
            'sections' => $scores
        ];
    }


    protected function sectionMatchScore(
        string $query,
        string $section
    ): int {

        $query = strtolower($query);

        $score = 0;


        if (str_contains($query, $section)) {
            $score += 100;
        }


        $keywords = [
            'method' => [
                'method',
                'approach',
                'architecture',
                'model'
            ],

            'dataset' => [
                'data',
                'dataset'
            ],

            'experiment' => [
                'experiment',
                'setup'
            ],

            'result' => [
                'result',
                'evaluation',
                'performance'
            ],

            'conclusion' => [
                'conclusion',
                'future'
            ]
        ];


        foreach ($keywords as $key => $items) {

            foreach ($items as $item) {

                if (
                    str_contains($query, $item)
                    &&
                    str_contains($section, $key)
                ) {
                    $score += 50;
                }
            }
        }


        return min($score, 100);
    }


    protected function generalScore(
        string $section
    ): int {

        return match (true) {

            str_contains($section, 'abstract') => 80,

            str_contains($section, 'introduction') => 70,

            str_contains($section, 'conclusion') => 60,

            str_contains($section, 'method') => 50,

            default => 30
        };
    }
}
