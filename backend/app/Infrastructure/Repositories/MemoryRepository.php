<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\SurrealDB;
use SurrealDB\SDK\Query\BoundQuery;
use App\Domain\Memory\DTOs\MemoryDTO;

class MemoryRepository
{
    // ──────────────── CREATE ────────────────

    public function create(string $userId, string $text, int $importance = 5, string $category = 'general', ?string $sourceMessage = null): MemoryDTO
    {
        $sql = "
            CREATE memory CONTENT {
                user_id: '$userId',
                text: '$text',
                importance: $importance,
                category: '$category',
                source_message: " . ($sourceMessage ? "'$sourceMessage'" : 'null') . ",
                last_used: time::now(),
                created_at: time::now()
            }
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return MemoryDTO::fromArray($result[0] ?? []);
    }

    // ──────────────── READ ────────────────

    public function find(string $id): ?MemoryDTO
    {
        try {
            $sql = "SELECT * FROM $id;";
            $result = SurrealDB::query(new BoundQuery($sql));

            return MemoryDTO::collectionFromArray($result)[0] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
    public function findByUser(string $userId, int $minImportance = 0, int $limit = 50): array
    {
        $sql = "
            SELECT * FROM memory
            WHERE user_id = '$userId'
            AND importance >= $minImportance
            ORDER BY importance DESC, last_used DESC
            LIMIT $limit
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return MemoryDTO::collectionFromArray($result);
    }

    public function findByCategory(string $userId, string $category, int $minImportance = 0): array
    {
        $sql = "
            SELECT * FROM memory
            WHERE user_id = '$userId'
            AND category = '$category'
            AND importance >= $minImportance
            ORDER BY importance DESC
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return MemoryDTO::collectionFromArray($result);
    }

    // ──────────────── UPDATE ────────────────

    public function update(string $id, array $data): ?MemoryDTO
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $fields[] = "$key = '$value'";
            } elseif (is_array($value)) {
                $fields[] = "$key = " . json_encode($value);
            } else {
                $fields[] = "$key = $value";
            }
        }
        $setClause = implode(', ', $fields);

        $sql = "
            UPDATE $id
            SET $setClause
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return isset($result[0]) ? MemoryDTO::fromArray($result[0]) : null;
    }

    public function updateLastUsed(string $id): ?MemoryDTO
    {
        $sql = "
            UPDATE $id
            SET last_used = time::now()
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return isset($result[0]) ? MemoryDTO::fromArray($result[0]) : null;
    }

    // ──────────────── DELETE ────────────────

    public function delete(string $id): bool
    {
        $sql = "DELETE $id;";
        $result = SurrealDB::query(new BoundQuery($sql));
        return !empty($result);
    }

    public function deleteByUser(string $userId): bool
    {
        $sql = "DELETE memory WHERE user_id = '$userId';";
        $result = SurrealDB::query(new BoundQuery($sql));
        return !empty($result);
    }
}
