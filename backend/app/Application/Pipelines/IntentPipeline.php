<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;
use App\Application\Intent\RuleIntentDetector;
use App\Application\DTOs\IntentResult;
use App\Domain\Contracts\PlannerProvider;

class IntentPipeline
{
    public function __construct(
        protected RuleIntentDetector $rules,
        protected PlannerProvider $planner
    ) {}

    public function process(
        ChatContext $context,
        bool $usePlanner = true
    ): ChatContext {

        /*
        |--------------------------------------------------------------------------
        | 1. Rule Detection First
        |--------------------------------------------------------------------------
        */
        $ruleResult = $this->rules->detect($context->currentMessage);

        if ($ruleResult instanceof IntentResult) {
            return $this->applyResult($context, $ruleResult);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Check if Planner should be executed
        |--------------------------------------------------------------------------
        */
        $isDocumentQuery = $this->isDocumentRelated($context->currentMessage);

        if (!$usePlanner || !$this->shouldUsePlanner($context, $isDocumentQuery)) {
            // ✅ Fallback: اگر پلنر اجرا نشد اما کلیدواژه سندی داشتیم، متادا را ست کن
            if ($isDocumentQuery) {
                $context->addMetadata('use_documents', true);
                $context->addMetadata('intent', 'document_search');
            }
            return $context;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Run Planner Analysis
        |--------------------------------------------------------------------------
        */
        $plannerResult = $this->planner->analyze($context);

        if ($plannerResult instanceof IntentResult) {
            return $this->applyResult($context, $plannerResult);
        }

        // ✅ Fallback نهایی
        if ($isDocumentQuery) {
            $context->addMetadata('use_documents', true);
            $context->addMetadata('intent', 'document_search');
        }

        return $context;
    }

    /**
     * بررسی اینکه آیا کئوری مربوط به اسناد/مقالات هست یا خیر (بدون محدودیت طول)
     */
    protected function isDocumentRelated(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));

        $keywords = [
            // فارسی
            'مقاله', 'پایان‌نامه', 'پایان نامه', 'سند', 'فایل', 'پی‌دی‌اف', 'تحقیق',
            'چکیده', 'چیکده', 'مقدمه', 'روش', 'متدولوژی', 'نتیجه', 'نتایج', 'بخش',
            'جدول', 'شکل', 'نویسنده', 'ارجاع', 'منابع', 'کد', 'معماری', 'پیاده‌سازی',
            // انگلیسی
            'paper', 'document', 'pdf', 'research', 'summary', 'abstract',
            'methodology', 'method', 'results', 'conclusion', 'section', 'architecture'
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * تصمیم‌گیری برای اجرا یا عدم اجرای Planner بر برحسب پیچیدگی یا کلیدواژه‌ها
     */
    protected function shouldUsePlanner(ChatContext $context, bool $isDocumentQuery): bool
    {
        $message = mb_strtolower(trim($context->currentMessage));

        // اگر کئوری مربوط به سند است، حتی اگر کوتاه باشد باید پلنر بررسی کند
        if ($isDocumentQuery) {
            return true;
        }

        // برای چت‌های عمومی فقط اگر طولانی و پیچیده باشند پلنر را درگیر کن
        return mb_strlen($message) >= 30;
    }

    protected function applyResult(
        ChatContext $context,
        IntentResult $result
    ): ChatContext {

        $context->addMetadata('intent', $result->intent);
        $context->addMetadata('confidence', $result->confidence);
        $context->addMetadata('needs_clarification', $result->needsClarification);
        $context->addMetadata('use_main_model', $result->useMainModel);
        $context->addMetadata('use_documents', $result->useDocuments);

        if (!empty($result->rewrite)) {
            $context->addMetadata('rewritten_message', $result->rewrite);
        }

        if ($result->needsClarification) {
            $context->setAssistantResponse(
                response: $result->rewrite ?? 'لطفاً درخواست خود را واضح‌تر بیان کنید.',
                tokenUsage: [
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0
                ]
            );
        }

        return $context;
    }
}
