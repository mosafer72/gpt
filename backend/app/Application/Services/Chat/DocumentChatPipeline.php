<?php

namespace App\Application\Services\Chat;


use App\Application\Context\ChatContext;
use App\Application\Services\Documents\DocumentRetrieverService;
use App\Application\Services\Documents\DocumentContextBuilder;
use App\Application\Services\Documents\AnswerEvidenceBuilder;
use App\Application\Pipelines\PromptPipeline;



class DocumentChatPipeline
{


    public function __construct(

        protected DocumentRetrieverService $retriever,

        protected DocumentContextBuilder $contextBuilder,

        protected AnswerEvidenceBuilder $evidenceBuilder,

        protected PromptPipeline $promptPipeline

    ) {

    }





    public function handle(

        ChatContext $context

    ): ChatContext {



        /*
        |--------------------------------------------------------------------------
        | Retrieve Documents
        |--------------------------------------------------------------------------
        */


        $chunks =

            $this->retriever
                ->retrieve(

                    workspaceId:
                    $context->workspaceId,

                    query:
                    $context->currentMessage,

                    documentIds:
                    $context->documentIds

                );






        /*
        |--------------------------------------------------------------------------
        | Build Context
        |--------------------------------------------------------------------------
        */


        $documentContext =

            $this->contextBuilder
                ->build(
                    $chunks
                );






        $context->documents =
            $documentContext;







        /*
        |--------------------------------------------------------------------------
        | Build Evidence
        |--------------------------------------------------------------------------
        */


        $evidence =

            $this->evidenceBuilder
                ->build(
                    $documentContext
                );



        $context->setEvidence(
            $evidence
        );






        /*
        |--------------------------------------------------------------------------
        | Build Prompt
        |--------------------------------------------------------------------------
        */


        return $this->promptPipeline
            ->build(
                $context
            );


    }



}
