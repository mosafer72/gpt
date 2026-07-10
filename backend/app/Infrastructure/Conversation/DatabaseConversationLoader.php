<?php

namespace App\Infrastructure\Conversation;

use App\Domain\Contracts\ConversationLoader;

class DatabaseConversationLoader implements ConversationLoader
{
    public function load(
        string $conversationId,
        int $limit = 20
    ): array {

        return \DB::table('messages')
            ->where(
                'chat_id',
                $conversationId
            )
            ->orderBy(
                'created_at',
                'asc'
            )
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
