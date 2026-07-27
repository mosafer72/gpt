<?php

namespace App\Application\Services\Chat;


use App\Domain\Contracts\PromptBuilder;
use App\Application\Context\ChatContext;
use App\Application\Prompts\SystemPrompt;



class DefaultPromptBuilder implements PromptBuilder
{


    public function build(
        ChatContext $context
    ): array {


        $messages = [];





        /*
        |--------------------------------------------------------------------------
        | System
        |--------------------------------------------------------------------------
        */


        $messages[] = [

            'role'=>'system',

            'content'=>
                SystemPrompt::default()

        ];









        /*
        |--------------------------------------------------------------------------
        | Memory Context
        |--------------------------------------------------------------------------
        */


        if(
            !empty($context->memories)
        ){


            $messages[]=[

                'role'=>'system',

                'content'=>

                    "Relevant user memory:\n\n"

                    .

                    json_encode(

                        $context->memories,

                        JSON_UNESCAPED_UNICODE

                    )

            ];


        }









        /*
        |--------------------------------------------------------------------------
        | Document Context
        |--------------------------------------------------------------------------
        */


        if(

            !empty(
                $context->metadata['document_context']
            )

        ){


            $messages[]=[


                'role'=>'system',


                'content'=>

                    $context
                    ->metadata['document_context']


            ];


        }









        /*
        |--------------------------------------------------------------------------
        | Conversation History
        |--------------------------------------------------------------------------
        */


        foreach(

            $context->messages

            as $message

        ){


            $messages[]=[


                'role'=>

                    $message->role,


                'content'=>

                    $message->content


            ];


        }









        /*
        |--------------------------------------------------------------------------
        | Current User Message
        |--------------------------------------------------------------------------
        */


        $messages[]=[


            'role'=>'user',


            'content'=>

                $context->currentMessage


        ];







        return $messages;


    }


}
