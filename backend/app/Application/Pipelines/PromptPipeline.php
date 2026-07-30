<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;
use App\Application\Prompts\WorkspacePromptResolver;
use App\Domain\Prompt\PromptCollection;

class PromptPipeline
{
    public function build(ChatContext $context): ChatContext
    {
        $prompt = new PromptCollection();

        // ۱. ساخت یک پیام System یکپارچه (شامل سیستم پرامپت اختصاصی ورک‌اسپیس، اطلاعات کاربر و سند کامل)
        $systemContent = $this->buildUnifiedSystemPrompt($context);
        $prompt->add(role: 'system', content: $systemContent);

        // ۲. اضافه کردن تاریخچه گفتگو و پیام فعلی کاربر
        $this->addConversation($prompt, $context);
        $this->addCurrentMessage($prompt, $context);

        $context->setPrompt($prompt->toArray());

        return $context;
    }

    /**
     * ترکیب System Prompt اختصاصی ورک‌اسپیس (یا پیش‌فرض)، Memories و متن کامل سند در یک پیام System واحد
     */
    protected function buildUnifiedSystemPrompt(ChatContext $context): string
    {
        $sections = [];

        // ✅ لود کردن سیستم پرامپت اختصاصی ورک‌اسپیس به صورت کاملاً پویا
        $systemPromptContent = WorkspacePromptResolver::resolve($context->workspace);

        $sections[] = $systemPromptContent;

        // اطلاعات و حافظه کاربر (در صورت وجود)
        $memoriesText = $this->formatMemories($context);
        if (!empty($memoriesText)) {
            $sections[] = "### اطلاعات کاربر:\n" . $memoriesText;
        }

        // متن کامل سند
        $documentText = $this->formatFullDocument($context);
        if (!empty($documentText)) {
            $sections[] = $documentText;
        }

        return implode("\n\n---------------------\n\n", array_filter($sections));
    }

    /**
     * قالب‌بندی و ارسال متن کامل سند جدید
     */
    protected function formatFullDocument(ChatContext $context): string
    {
        if (empty($context->documents)) {
            return '';
        }

        $header = <<<TEXT
شما به سوالات کاربر بر اساس سند زیر پاسخ می‌دهید.

قوانین پاسخگویی:
- مبنای اصلی پاسخ شما متن کامل سند زیر است.
- پاسخ‌ها را دقیق، تحلیلی و کاملاً مستند بر اساس متن ارائه دهید.
- اگر پاسخ سوال در سند موجود نبود، دقیقاً اعلام کنید که موضوع در سند موجود نیست.
TEXT;

        $documentsContent = [];

        foreach ($context->documents as $document) {
            $text = $this->extractContent($document);
            if (empty($text)) {
                continue;
            }

            $metadata = $this->extractMetadata($document);
            $title = $metadata['title'] ?? 'سند اصلی';

            // نرمال‌سازی فاصله‌ها برای جلوگیری از چسبیدن کلمات در متن‌های استخراج‌شده از PDF
            $cleanedText = $this->normalizeTextSpacing($text);

            $documentsContent[] = <<<TEXT
--- [سند: {$title}] ---
{$cleanedText}
TEXT;
        }

        if (empty($documentsContent)) {
            return '';
        }

        return $header . "\n\n" . implode("\n\n", $documentsContent);
    }

    protected function formatMemories(ChatContext $context): string
    {
        if (empty($context->memories)) {
            return '';
        }

        $lines = [];
        foreach ($context->memories as $memory) {
            $text = is_object($memory) ? ($memory->text ?? '') : ($memory['text'] ?? '');
            if (!empty($text)) {
                $lines[] = "- " . trim($text);
            }
        }

        return implode("\n", $lines);
    }

    protected function addConversation(PromptCollection $prompt, ChatContext $context): void
    {
        if (empty($context->messages)) {
            return;
        }

        $lastIndex = count($context->messages) - 1;

        foreach ($context->messages as $index => $message) {
            if ($index === $lastIndex && $message->role === 'user') {
                continue;
            }

            $prompt->add(
                role: $message->role,
                content: $message->content
            );
        }
    }

    protected function addCurrentMessage(PromptCollection $prompt, ChatContext $context): void
    {
        if (blank($context->currentMessage)) {
            return;
        }

        $prompt->add(
            role: 'user',
            content: $context->currentMessage
        );
    }

    // --- توابع کمکی ---

    protected function extractContent(mixed $document): string
    {
        if (is_object($document) && property_exists($document, 'content')) {
            return trim((string) $document->content);
        }
        if (is_array($document)) {
            return trim($document['content'] ?? '');
        }
        return '';
    }

    protected function extractMetadata(mixed $document): array
    {
        if (is_object($document) && property_exists($document, 'metadata')) {
            return (array) $document->metadata;
        }
        if (is_array($document)) {
            return $document['metadata'] ?? [];
        }
        return [];
    }

    /**
     * اصلاح و نرمال‌سازی فاصله‌های متون استخراج شده از PDF جهت جلوگیری از چسبیدن کلمات
     */
    /**
     * اصلاح، نرمال‌سازی و ساختاردهی نهایی متون PDF (بهینه‌شده برای پایان‌نامه، مقالات علمی و RAG)
     */
    protected function normalizeTextSpacing(string $text): string
    {
        if (blank($text)) {
            return '';
        }

        // ۱. پیش‌پردازش و یکسان‌سازی فاصله‌های یونیکد غیراستاندارد PDF (NBSP) در کل متن
        $text = str_replace("\u{00A0}", ' ', $text);

        // ۲. تفکیک لیست‌های شماره‌داری که در یک خط هستند (با شرط شروع با حرف بزرگ برای جلوگیری از خطا در اعداد اعشاری مثل 1.5)
        $text = preg_replace('/([^\n])\s+(\d+\.)\s+(?=[A-Z])/u', "$1\n\n$2 ", $text);

        // ۳. حفظ و استانداردسازی لیست‌های شماره‌دار که در خط جدید هستند
        $text = preg_replace('/\n(\s*)(\d+\.)\s+(?=[A-Z])/u', "\n\n$2 ", $text);

        // ۴. حفظ و ساختاردهی بولت‌پوینت‌ها (• و -)
        $text = preg_replace('/\n\s*([•\-])\s+/u', "\n\n$1 ", $text);

        // ۵. تبدیل نیولاین‌های شکسته‌ی وسط پاراگراف به فاصله (حفظ ساختار لیست‌ها و پاراگراف‌ها)
        $text = preg_replace("/(?<!\n)\n(?!\n)/u", " ", $text);

        // ۶. یکسان‌سازی فاصله‌های بین پاراگراف‌ها (حداکثر ۲ نیولاین)
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        $lines = explode("\n", $text);
        $processedLines = [];

        foreach ($lines as $line) {
            // ۷. یکسان‌سازی NBSP و تریم اولیه برای هر خط
            $line = str_replace("\u{00A0}", ' ', $line);
            $trimmedLine = trim($line);

            // ۸. محافظت کامل از کدهای مارک‌دان و URLها
            if (
                str_starts_with($trimmedLine, '```') ||
                str_starts_with($trimmedLine, '`') ||
                str_contains($trimmedLine, 'http://') ||
                str_contains($trimmedLine, 'https://')
            ) {
                $processedLines[] = $line;
                continue;
            }

            // ۹. تشخیص فصول پایان‌نامه (Chapter 1 / CHAPTER 2) به عنوان هدر اصلی سطح ۱ (#)
            if (preg_match('/^(chapter\s+\w+|CHAPTER\s+\d+)/i', $trimmedLine)) {
                $processedLines[] = "# " . $trimmedLine;
                continue;
            }

            // ۱۰. تشخیص پیشرفته سرفصل‌ها و زیربخش‌های مقاله (با محدودیت طول کمتر از ۸۰ کاراکتر برای دقت بالا)
            if (
                preg_match('/^\d+(\.\d+)*\.?\s+[A-Z][A-Za-z\s\-]{2,}$/u', $trimmedLine)
                && mb_strlen($trimmedLine) < 80
            ) {
                $processedLines[] = "## " . $trimmedLine;
                continue;
            }

            // ۱۱. حذف فاصله‌های اضافی چندگانه و تب‌ها
            $line = preg_replace('/[ \t\r]+/', ' ', $line);

            // ۱۲. ایجاد فاصله امن بین حروف فارسی و حروف انگلیسی (حفظ اعداد و اسامی مدل‌ها مثل ResNet50 یا BERT2)
            $line = preg_replace('/([ا-یأإؤءةچپژگهی])([a-zA-Z])/', '$1 $2', $line);
            $line = preg_replace('/([a-zA-Z])([ا-یأإؤءةچپژگهی])/', '$1 $2', $line);

            // ۱۳. اصلاح علائم نگارشی اختصاصی فارسی
            $line = preg_replace('/([،؛؟])([^\s])/u', '$1 $2', $line);

            $processedLines[] = $line;
        }

        // ۱۴. تجمیع نهایی و پاکسازی خطوط خالی اضافی ایجاد شده توسط هدرها
        $finalText = implode("\n", $processedLines);
        $finalText = preg_replace("/\n{3,}/u", "\n\n", $finalText);

        return trim($finalText);
    }
}
