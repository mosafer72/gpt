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
        | Rule Based Detection
        |--------------------------------------------------------------------------
        */

        $result =
            $this->rules->detect(
                $context->currentMessage
            );



        if ($result instanceof IntentResult) {

            return $this->applyResult(
                $context,
                $result
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Planner Detection
        |--------------------------------------------------------------------------
        */

        if ($usePlanner) {


            $result =
                $this->planner->analyze(
                    $context
                );



            if ($result instanceof IntentResult) {


                return $this->applyResult(
                    $context,
                    $result
                );

            }

        }



        return $context;

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
            'rewritten_message',
            $result->rewrite
        );


        $context->addMetadata(
            'use_main_model',
            $result->useMainModel
        );



        /*
        |--------------------------------------------------------------------------
        | Stop pipeline if clarification needed
        |--------------------------------------------------------------------------
        */

        if ($result->needsClarification) {


            $context->setAssistantResponse(

                response:
                    $result->rewrite,

                tokenUsage:
                [
                    'prompt_tokens'=>0,

                    'completion_tokens'=>0,

                    'total_tokens'=>0,
                ]

            );

        }



        return $context;

    }

}
