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

            'user_id' => $message->metadata['user_id'] ?? null,

            'role' => $message->role,

            'content' => $message->content,

            'created_at' => now(),

            'updated_at' => now(),

        ]);

    }

}
