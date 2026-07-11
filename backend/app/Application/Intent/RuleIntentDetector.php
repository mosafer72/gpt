<?php

namespace App\Application\Intent;

use App\Application\DTOs\IntentResult;

class RuleIntentDetector

{

    public function detect(string $message): ?IntentResult

    {

        $text = mb_strtolower($message);

        if (str_contains($text, 'pdf') || str_contains($text, 'پی‌دی‌اف')) {

            return new IntentResult(

                intent: 'pdf',

                confidence: 0.95,

                rewrite: $message

            );

        }

        if (str_contains($text, 'اکسل') || str_contains($text, 'excel')) {

            return new IntentResult(

                intent: 'xlsx',

                confidence: 0.95,

                rewrite: $message

            );

        }

        if (str_contains($text, 'تصویر') || str_contains($text, 'عکس')) {

            return new IntentResult(

                intent: 'image',

                confidence: 0.95,

                rewrite: $message

            );

        }

        if (str_contains($text, 'ورد') || str_contains($text, 'docx')) {

            return new IntentResult(

                intent: 'docx',

                confidence: 0.95,

                rewrite: $message

            );

        }

        return null;

    }

}
