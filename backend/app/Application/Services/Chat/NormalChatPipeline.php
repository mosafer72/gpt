<?php

namespace App\Application\Services\Chat;


use App\Application\Context\ChatContext;
use App\Application\Pipelines\PromptPipeline;



class NormalChatPipeline
{


    public function __construct(

        protected PromptPipeline $promptPipeline

    ){

    }





    public function handle(
        ChatContext $context
    ): ChatContext {


        /*
        |--------------------------------------------------------------------------
        | No document retrieval
        |--------------------------------------------------------------------------
        */


        return $this->promptPipeline
            ->build(
                $context
            );


    }



}
