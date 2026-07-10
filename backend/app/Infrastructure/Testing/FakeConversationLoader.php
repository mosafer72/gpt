<?php

namespace App\Infrastructure\Testing;


use App\Domain\Contracts\ConversationLoader;


class FakeConversationLoader implements ConversationLoader
{


    public function load(
        string $conversationId,
        int $limit = 20
    ): array {


        return [

            (object)[
                "role"=>"user",
                "content"=>"سلام"
            ],


            (object)[
                "role"=>"assistant",
                "content"=>"سلام، چطور کمکت کنم؟"
            ]

        ];

    }

}
