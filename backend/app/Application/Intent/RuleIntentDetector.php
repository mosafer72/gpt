<?php

namespace App\Application\Intent;

use App\Application\DTOs\IntentResult;

class RuleIntentDetector
{
    public function detect(string $message): ?IntentResult
    {
        $text = mb_strtolower(trim($message));

        if ($text === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | ۱. بررسی احوال‌پرسی‌ها و چت‌های ساده (Exact Match / Short Text)
        |--------------------------------------------------------------------------
        | فقط پیام‌های کوتاه و دقیق به عنوان چت ساده در نظر گرفته می‌شوند.
        */
        $exactSimpleChats = [
            'سلام',
            'سلام خوبی',
            'خوبی',
            'چه خبر',
            'چطوری',
            'حالت چطوره',
            'مرسی',
            'ممنون',
            'دمت گرم',
            'خداحافظ',
            'hi',
            'hello',
            'thanks',
            'thank you'
        ];

        // اگر پیام دقیقاً یکی از عبارات بالاست
        if (in_array($text, $exactSimpleChats, true)) {
            return new IntentResult(
                intent: 'chat',
                confidence: 0.99,
                rewrite: $message,
                needsClarification: false,
                useMainModel: false
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ۲. بررسی پسوندها و کلیدواژه‌های فایل‌ها (تخصصی)
        |--------------------------------------------------------------------------
        | در صورتی که کاربر درباره فایل‌ها سوال بپرسد، مستقیماً به مدل اصلی ارجاع داده می‌شود.
        */

        // PDF
        if ($this->hasKeyword($text, ['pdf', 'پی‌دی‌اف', 'پی دی اف'])) {
            return new IntentResult(
                intent: 'pdf',
                confidence: 0.95,
                rewrite: $message,
                needsClarification: false,
                useMainModel: true
            );
        }

        // Excel
        if ($this->hasKeyword($text, ['excel', 'xlsx', 'اکسل'])) {
            return new IntentResult(
                intent: 'xlsx',
                confidence: 0.95,
                rewrite: $message,
                needsClarification: false,
                useMainModel: true
            );
        }

        // Image
        if ($this->hasKeyword($text, ['تصویر', 'عکس', 'image', 'photo', 'png', 'jpg', 'jpeg'])) {
            return new IntentResult(
                intent: 'image',
                confidence: 0.95,
                rewrite: $message,
                needsClarification: false,
                useMainModel: true
            );
        }

        // DOCX
        if ($this->hasKeyword($text, ['word', 'docx', 'ورد'])) {
            return new IntentResult(
                intent: 'docx',
                confidence: 0.95,
                rewrite: $message,
                needsClarification: false,
                useMainModel: true
            );
        }

        return null;
    }

    /**
     * متد کمکی برای بررسی وجود کلیدواژه در متن
     */
    protected function hasKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
