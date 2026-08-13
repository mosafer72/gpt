<?php

namespace App\Infrastructure\Conversation;

use App\Domain\Contracts\ConversationLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DatabaseConversationLoader implements ConversationLoader
{
    public function load(
        string $conversationId,
        int $limit = 20
    ): array {
        $cacheKey = "conversation:{$conversationId}:history:{$limit}";

        return Cache::remember(
            $cacheKey,
            now()->addHours(1),
            function () use ($conversationId, $limit) {

                return DB::table('messages')
                    ->where('chat_id', $conversationId)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->limit($limit)
                    ->get()
                    ->toArray();
            }
        );
    }
}
