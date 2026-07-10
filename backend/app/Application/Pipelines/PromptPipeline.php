<?php

namespace App\Application\Pipelines;


use App\Application\Context\ChatContext;
use App\Domain\Prompt\PromptCollection;



class PromptPipeline
{


    public function build(
        ChatContext $context
    ): ChatContext {


        $prompt = new PromptCollection();



        /**
         * 1 - System Prompt
         */
        $this->addSystemPrompt(
            $prompt
        );



        /**
         * 2 - Memory Context
         */
        $this->addMemories(
            $prompt,
            $context
        );



        /**
         * 3 - Conversation History
         */
        $this->addConversation(
            $prompt,
            $context
        );



        /**
         * 4 - Current User Message
         */
        $prompt->add(
            role:'user',
            content:$context->currentMessage
        );



        $context->setPrompt(
            $prompt->toArray()
        );



        return $context;

    }





    protected function addSystemPrompt(
        PromptCollection $prompt
    ): void {


        $prompt->add(
            role:'system',
            content:
            'You are a helpful AI assistant.'
        );

    }






    protected function addMemories(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        if(empty($context->memories)){
            return;
        }



        $memoryText = "User memories:\n";


        foreach($context->memories as $memory){

            $memoryText .=
                "- ".$memory->text."\n";

        }



        $prompt->add(
            role:'system',
            content:$memoryText
        );

    }





    protected function addConversation(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        foreach($context->messages as $message){


            $prompt->add(
                role:$message->role,
                content:$message->content
            );

        }

    }


}
