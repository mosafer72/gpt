<?php

namespace App\Infrastructure\Conversation;

use SurrealDB\Laravel\Facades\SurrealDB;
use SurrealDB\SDK\Query\BoundQuery;
use App\Domain\Conversation\DTOs\ConversationDTO;

class SurrealConversationRepository
{
    // ──────────────── CREATE ────────────────

    public function create(string $userId, array $data): ConversationDTO
    {


        $sql = "
            CREATE conversation CONTENT {
                user_id: '$userId',
                title: '{$data['title']}',
                model: '{$data['model']}',
                status: 'active',
                created_at: time::now(),
                updated_at: time::now()
            }
            RETURN *;
        ";


        // 🧪 دیباگ: کوئری را لاگ کنید
        \Log::debug('CREATE SQL: ' . $sql);

        $result = SurrealDB::query(new BoundQuery($sql));

        // 🧪 دیباگ: ساختار نتیجه را چاپ کنید (در Tinker کار می‌کند)
        // اگر در Tinker هستید، از dd($result) استفاده کنید
        // dd($result);

        // اگر نتیجه خالی بود، یک خطای واضح بدهید
        if (empty($result) || !isset($result[0])) {
            throw new \RuntimeException('Failed to create conversation: no data returned.');
        }

        // 🧪 دیباگ: ببینید کلیدهای موجود در رکورد چیست
        // dd(array_keys($result[0]));

        // حالا از داده‌ها DTO بسازید
        return ConversationDTO::fromArray($result[0]);
    }

    // ──────────────── READ ────────────────

    public function find(string $id): ?ConversationDTO
    {
        try {

            $sql = "SELECT * FROM $id;";
            $result = SurrealDB::query(new BoundQuery($sql));

            return ConversationDTO::collectionFromArray($result)[0] ?? null;

        } catch (\Throwable $e) {

            return null;

        }
    }

    public function findByUser(string $userId): array
    {
        $sql = "
            SELECT * FROM conversation
            WHERE user_id = '$userId'
            AND status = 'active'
            ORDER BY created_at DESC
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return ConversationDTO::collectionFromArray($result);
    }

    // ──────────────── UPDATE ────────────────

    public function update(string $id, array $data): ?ConversationDTO
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $fields[] = "$key = '$value'";
            } else {
                $fields[] = "$key = $value";
            }
        }
        $setClause = implode(', ', $fields);

        $sql = "
            UPDATE $id
            SET $setClause, updated_at = time::now()
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return isset($result[0]) ? ConversationDTO::fromArray($result[0]) : null;
    }

    // ──────────────── SOFT DELETE ────────────────

    public function softDelete(string $id): ?ConversationDTO
    {
        $sql = "
            UPDATE $id
            SET status = 'deleted', updated_at = time::now()
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return isset($result[0]) ? ConversationDTO::fromArray($result[0]) : null;
    }

    // ──────────────── RESTORE ────────────────

    public function restore(string $id): ?ConversationDTO
    {
        $sql = "
            UPDATE $id
            SET status = 'active', updated_at = time::now()
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return isset($result[0]) ? ConversationDTO::fromArray($result[0]) : null;
    }

    // ──────────────── ARCHIVE ────────────────

    public function archive(string $id): ?ConversationDTO
    {
        $sql = "
            UPDATE $id
            SET status = 'archived', updated_at = time::now()
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return isset($result[0]) ? ConversationDTO::fromArray($result[0]) : null;
    }

    // ──────────────── HARD DELETE ────────────────

    public function hardDelete(string $id): bool
    {
        $sql = "DELETE $id;";
        $result = SurrealDB::query(new BoundQuery($sql));
        return !empty($result);
    }

    // ──────────────── BATCH OPERATIONS ────────────────

    public function softDeleteByUser(string $userId): array
    {
        $sql = "
            UPDATE conversation
            SET status = 'deleted', updated_at = time::now()
            WHERE user_id = '$userId'
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return ConversationDTO::collectionFromArray($result);
    }

    public function archiveByUser(string $userId): array
    {
        $sql = "
            UPDATE conversation
            SET status = 'archived', updated_at = time::now()
            WHERE user_id = '$userId'
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return ConversationDTO::collectionFromArray($result);
    }

    public function hardDeleteByUser(string $userId): bool
    {
        $sql = "DELETE conversation WHERE user_id = '$userId';";
        $result = SurrealDB::query(new BoundQuery($sql));
        return !empty($result);
    }
}
