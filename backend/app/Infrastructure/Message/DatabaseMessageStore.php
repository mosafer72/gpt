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

            'type' => $message->type,

            'content' => $message->content,

            'image_url' => $message->image_url,

            'metadata' => json_encode(
                $message->metadata ?? []
            ),

            'created_at' => now(),

            'updated_at' => now(),

        ]);

    }

}
