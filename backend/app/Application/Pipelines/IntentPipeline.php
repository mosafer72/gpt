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

    ) {
    }







    public function process(
        ChatContext $context,
        bool $usePlanner = true
    ): ChatContext {


        /*
        |--------------------------------------------------------------------------
        | Rule Detection First
        |--------------------------------------------------------------------------
        */


        $ruleResult =
            $this->rules
                ->detect(

                    $context->currentMessage

                );





        if (
            $ruleResult instanceof IntentResult
        ) {


            return $this->applyResult(

                $context,

                $ruleResult

            );


        }








        /*
        |--------------------------------------------------------------------------
        | Planner Only For Complex Requests
        |--------------------------------------------------------------------------
        */


        if (
            !$usePlanner
        ) {

            return $context;

        }







        if (
            !$this->shouldUsePlanner(
                $context
            )
        ) {


            return $context;


        }







        $plannerResult =
            $this->planner
                ->analyze(

                    $context

                );






        if (
            $plannerResult instanceof IntentResult
        ) {


            return $this->applyResult(

                $context,

                $plannerResult

            );


        }






        return $context;


    }









    protected function shouldUsePlanner(
        ChatContext $context
    ): bool {


        $message =
            mb_strtolower(
                trim(
                    $context->currentMessage
                )
            );





        /*
        |--------------------------------------------------------------------------
        | Short casual messages
        |--------------------------------------------------------------------------
        */


        if (
            mb_strlen($message) < 20
        ) {


            return false;


        }






        /*
        |--------------------------------------------------------------------------
        | Document / Academic Requests
        |--------------------------------------------------------------------------
        */


        $keywords = [

            'مقاله',

            'paper',

            'document',

            'pdf',

            'تحقیق',

            'research',

            'summary',

            'abstract',

            'methodology',

            'نتیجه',

            'بخش'

        ];






        foreach ($keywords as $keyword) {


            if (
                str_contains(
                    $message,
                    $keyword
                )
            ) {


                return true;


            }


        }






        return false;


    }









    protected function applyResult(
        ChatContext $context,
        IntentResult $result
    ): ChatContext {


        $context->addMetadata(

            'intent',

            $result->intent

        );





        $context->addMetadata(

            'confidence',

            $result->confidence

        );





        $context->addMetadata(

            'needs_clarification',

            $result->needsClarification

        );


        $context->addMetadata(
            'use_main_model',
            $result->useMainModel
        );


        $context->addMetadata(
            'use_documents',
            $result->useDocuments
        );


        if (
            !empty(
            $result->rewrite
        )
        ) {


            $context->addMetadata(

                'rewritten_message',

                $result->rewrite

            );


        }






        $context->addMetadata(

            'use_main_model',

            $result->useMainModel

        );







        if (
            $result->needsClarification
        ) {



            $context->setAssistantResponse(

                response:

                $result->rewrite
                ??
                'لطفاً درخواست خود را واضح‌تر بیان کنید.',



                tokenUsage:

                [

                    'prompt_tokens' => 0,

                    'completion_tokens' => 0,

                    'total_tokens' => 0

                ]

            );



        }







        return $context;


    }


}
