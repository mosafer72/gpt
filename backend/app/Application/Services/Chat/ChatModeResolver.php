<?php

namespace App\Application\Services\Chat;


use App\Application\Context\ChatContext;



class ChatModeResolver
{


    public function resolve(
        ChatContext $context
    ): string {


        /*
        |--------------------------------------------------------------------------
        | Document Mode
        |--------------------------------------------------------------------------
        */


        if (
            !empty(
            $context->documents
        )
        ) {

            return 'document';

        }



        if (
            !empty(
            $context->documentIds
        )
        ) {

            return 'document';

        }




        /*
        |--------------------------------------------------------------------------
        | Normal Chat
        |--------------------------------------------------------------------------
        */


        return 'chat';


    }



}
