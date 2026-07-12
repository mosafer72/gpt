<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;

use App\Application\Intent\RuleIntentDetector;

use App\Domain\Contracts\PlannerProvider;

class IntentPipeline
{

    public function __construct(

        protected RuleIntentDetector $rules,

        protected PlannerProvider $planner

    ) {
    }

    public function process(ChatContext $context): ChatContext
    {
        $text = mb_strtolower(trim($context->currentMessage));

        $simpleMessages = [
            'سلام',
            'سلامم',
            'خوبی',
            'چطوری',
            'hi',
            'hello',
            'hey',
            'ok',
            'اوکی',
            'مرسی',
            'ممنون'
        ];

        // پیام‌های ساده مستقیماً به مدل اصلی بروند
        if (in_array($text, $simpleMessages)) {
            $context->metadata['intent'] = 'chat';
            $context->metadata['confidence'] = 1;
            $context->metadata['needs_clarification'] = false;

            return $context;
        }

        // ابتدا Rule-based
        $result = $this->rules->detect($context->currentMessage);

        // اگر Rule پیدا نشد → Planner
        if (!$result) {
            $result = $this->planner->analyze($context->currentMessage);
        }

        $context->metadata['intent'] = $result->intent;
        $context->metadata['confidence'] = $result->confidence;
        $context->metadata['needs_clarification'] = $result->needsClarification;

        // پرامپت بازنویسی شده
        $context->currentMessage = $result->rewrite;

        return $context;
    }
}
