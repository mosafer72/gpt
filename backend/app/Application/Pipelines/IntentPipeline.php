<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;
use App\Application\Intent\RuleIntentDetector;
use App\Domain\Contracts\PlannerProvider;
use App\Models\Usage;

class IntentPipeline
{

    public function __construct(

        protected RuleIntentDetector $rules,

        protected PlannerProvider $planner

    ) {
    }


    public function process(
        ChatContext $context,
        bool $usePlanner = false
    ): ChatContext {


        $text = mb_strtolower(
            trim($context->currentMessage)
        );


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



        /**
         * پیام‌های ساده
         */
        if (
            in_array($text, $simpleMessages)
        ) {

            $context->metadata['intent'] = 'chat';

            $context->metadata['confidence'] = 1;

            $context->metadata['needs_clarification'] = false;


            return $context;

        }



        /**
         * انتخاب روش تشخیص
         */
        if ($usePlanner) {

            $result = $this->planner->analyze($context);

        } else {

            $result = $this->rules->detect($context);
        }



        /**
         * اگر Rule چیزی پیدا نکرد
         */
        // if (!$result && $usePlanner) {


        //     $result =
        //         $this->planner
        //             ->analyze(
        //                 $context->currentMessage
        //             );

        // }



        /**
         * ذخیره نتیجه
         */
        if ($result) {

            $context->metadata['intent'] = $result->intent;

            $context->metadata['confidence'] = $result->confidence;

            $context->metadata['needs_clarification'] = $result->needsClarification;

            // بازنویسی را در metadata نگه دار
            $context->metadata['rewritten_message'] = $result->rewrite;

            // اگر نیاز به سؤال تکمیلی دارد
            if ($result->needsClarification) {

                $context->setAssistantResponse(
                    response: $result->rewrite,
                    tokenUsage: [
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'total_tokens' => 0,
                    ]
                );
            }
        }



        return $context;

    }

}
