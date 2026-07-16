<?php

namespace App\Infrastructure\Message;

use App\Domain\Contracts\MessageStore;
use App\Domain\Message\DTOs\MessageDTO;
use Illuminate\Support\Facades\DB;


class DatabaseMessageStore implements MessageStore
{

    public function store(
        MessageDTO $message
    ): void {


        DB::table('messages')->insert([

            'chat_id' => $message->conversation,

            'user_id' => $message->user_id,

            'role' => $message->role,

            'content' => $message->content,

            'metadata' => json_encode(
                $message->metadata ?? []
            ),

            'created_at' => now(),

            'updated_at' => now(),

        ]);

    }

}
