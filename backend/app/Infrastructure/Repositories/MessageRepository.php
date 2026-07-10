<?php

namespace App\Infrastructure\Repositories;

use SurrealDB\Laravel\Facades\SurrealDB;
use SurrealDB\SDK\Query\BoundQuery;
use App\Domain\Message\DTOs\MessageDTO;

class MessageRepository
{
    // ──────────────── CREATE ────────────────

    public function create(string $conversationId, string $role, string $content, int $tokenCount = 0, ?array $metadata = null): MessageDTO
    {
        $metadataJson = $metadata ? json_encode($metadata) : '{}';
        $sql = "
            CREATE message CONTENT {
                conversation: $conversationId,
                role: '$role',
                content: '$content',
                token_count: $tokenCount,
                metadata: $metadataJson,
                created_at: time::now()
            }
            RETURN *;
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return MessageDTO::fromArray($result[0] ?? []);
    }

    // ──────────────── READ ────────────────

    public function find(string $id): ?MessageDTO
    {
        $sql = "SELECT * FROM $id;";
        $result = SurrealDB::query(new BoundQuery($sql));

        return MessageDTO::collectionFromArray($result)[0] ?? null;
    }

    public function findByConversation(string $conversationId, int $limit = 50): array
    {
        $sql = "
            SELECT * FROM message
            WHERE conversation = $conversationId
            ORDER BY created_at ASC
            LIMIT $limit
        ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return MessageDTO::collectionFromArray($result);
    }

    public function findLast(string $conversationId, int $limit = 10): array
    {
        $sql = "
        SELECT * FROM message
        WHERE conversation = $conversationId
        ORDER BY created_at DESC
        LIMIT $limit
    ";

        $result = SurrealDB::query(new BoundQuery($sql));
        return MessageDTO::collectionFromArray($result);
    }

    // ──────────────── UPDATE ────────────────

    public function update(string $id, array $data): ?MessageDTO
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
        return isset($result[0]) ? MessageDTO::fromArray($result[0]) : null;
    }

    // ──────────────── DELETE ────────────────

    public function delete(string $id): bool
    {
        $sql = "DELETE $id;";
        $result = SurrealDB::query(new BoundQuery($sql));
        return !empty($result);
    }

    public function deleteByConversation(string $conversationId): bool
    {
        $sql = "DELETE message WHERE conversation = $conversationId;";
        $result = SurrealDB::query(new BoundQuery($sql));
        return !empty($result);
    }
}
