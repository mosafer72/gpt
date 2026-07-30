<?php

namespace App\Application\Services\Documents;

class QueryIntentClassifier
{
    protected array $sectionAliases = [
        'abstract' => [
            'abstract',
            'چکیده',
            'چکیده مقاله'
        ],

        'introduction' => [
            'introduction',
            'intro',
            'مقدمه'
        ],

        'methodology' => [
            'method',
            'methodology',
            'approach',
            'architecture',
            'model',
            'روش',
            'روش شناسی',
            'معماری',
            'مدل'
        ],

        'dataset' => [
            'dataset',
            'datasets',
            'data',
            'داده',
            'دیتاست'
        ],

        'experiment' => [
            'experiment',
            'experiments',
            'evaluation',
            'آزمایش',
            'ارزیابی'
        ],

        'results' => [
            'result',
            'results',
            'findings',
            'نتایج'
        ],

        'discussion' => [
            'discussion',
            'بحث'
        ],

        'conclusion' => [
            'conclusion',
            'future work',
            'نتیجه گیری',
            'جمع بندی'
        ]
    ];


    public function classify(string $query): array
    {
        $query = $this->normalizeText($query);


        foreach ($this->sectionAliases as $section => $words) {

            foreach ($words as $word) {

                if (str_contains($query, mb_strtolower($word))) {

                    return [
                        'type' => $section,
                        'all' => [$section],
                        'confidence' => 0.95,
                        'section' => $section
                    ];
                }
            }
        }


        $patterns = [
            'title' => [
                'title',
                'paper title',
                'paper name',
                'عنوان مقاله',
                'نام مقاله'
            ],

            'author' => [
                'author',
                'authors',
                'نویسنده',
                'نویسندگان'
            ],

            'keywords' => [
                'keywords',
                'key words',
                'کلیدواژه',
                'کلمات کلیدی'
            ],

            'summary' => [
                'summary',
                'overview',
                'خلاصه',
                'مرور کلی'
            ],

            'comparison' => [
                'compare',
                'comparison',
                'مقایسه',
                'تفاوت'
            ],

            'explanation' => [
                'explain',
                'describe',
                'توضیح',
                'چگونه',
                'چرا'
            ]
        ];


        foreach ($patterns as $type=>$words) {

            foreach ($words as $word) {

                if (str_contains($query, mb_strtolower($word))) {

                    return [
                        'type'=>$type,
                        'all'=>[$type],
                        'confidence'=>0.9
                    ];
                }
            }
        }


        return [
            'type'=>'general',
            'confidence'=>0.5
        ];
    }


    protected function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $text = str_replace(
            ['ي','ك','‌'],
            ['ی','ک',' '],
            $text
        );

        $text = str_replace(
            ['چیکده','چکید'],
            'چکیده',
            $text
        );

        return $text;
    }
}
