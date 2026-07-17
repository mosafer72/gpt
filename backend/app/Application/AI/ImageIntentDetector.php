<?php

namespace App\Application\AI;

class ImageIntentDetector
{
    public function detect(string $message): ?array
    {
        $keywords = [
            'تصویر',
            'عکس',
            'نقاشی',
            'بساز',
            'طراحی کن',
            'draw',
            'create image',
            'generate image',
            'painting',
        ];

        foreach ($keywords as $keyword) {
            if (mb_stripos($message, $keyword) !== false) {
                return [
                    'action' => 'generate_image',
                    'prompt' => trim($message),
                    'size'   => '1024x1024',
                ];
            }
        }

        return null;
    }
}
