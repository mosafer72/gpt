<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\ConversationRepository;
use App\Domain\Conversation\DTOs\ConversationDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DatabaseConversationRepository implements ConversationRepository
{
    public function find(string $id): ?ConversationDTO
    {
        $chat = DB::table('chats')
            ->where('id', $id)
            ->first();

        if (!$chat) {
            return null;
        }

        return new ConversationDTO(
            id: (string) $chat->id,
            userId: (string) $chat->user_id,
            title: $chat->title ?? 'New Conversation',
            model: $chat->model ?? 'gpt-4o',
            status: $chat->status ?? 'active',
            createdAt: (string) $chat->created_at,
            updatedAt: (string) $chat->updated_at,
        );
    }

    public function create(
        string $userId,
        ?string $title = null,
        ?string $model = null
    ): ConversationDTO {

        $id = DB::table('chats')
            ->insertGetId([
                'user_id' => $userId,
                'title' => $title,
                'model' => $model,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // پاک کردن کش لیست چت‌های کاربر
        Cache::forget("user:{$userId}:recent_chats");

        return $this->find((string) $id);
    }

    public function update(string $id, array $data): void
    {
        $chat = DB::table('chats')
            ->where('id', $id)
            ->first();

        DB::table('chats')
            ->where('id', $id)
            ->update([
                ...$data,
                'updated_at' => now(),
            ]);

        if ($chat) {
            Cache::forget("user:{$chat->user_id}:recent_chats");
        }
    }

    public function delete(string $id): void
    {
        $chat = DB::table('chats')
            ->where('id', $id)
            ->first();

        DB::table('chats')
            ->where('id', $id)
            ->delete();

        if ($chat) {
            Cache::forget("user:{$chat->user_id}:recent_chats");
            Cache::forget("chat:{$chat->id}:messages");
        }
    }

    // کش ۲۰ چت آخر کاربر
    public function recentForUser(int $userId, int $limit = 20)
    {
        return Cache::remember(
            "user:{$userId}:recent_chats",
            now()->addMinutes(10),
            function () use ($userId, $limit) {
                return DB::table('chats')
                    ->where('user_id', $userId)
                    ->orderByDesc('updated_at')
                    ->limit($limit)
                    ->get();
            }
        );
    }
}
