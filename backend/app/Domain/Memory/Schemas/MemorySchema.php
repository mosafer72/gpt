<?php

namespace App\Domain\Memory\Schemas;

use Surqlize\Model\SchemaContract;

class MemorySchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            "DEFINE TABLE IF NOT EXISTS memory SCHEMAFULL;",
            "DEFINE FIELD IF NOT EXISTS user_id ON memory TYPE string;",
            "DEFINE FIELD IF NOT EXISTS text ON memory TYPE string;",
            "DEFINE FIELD IF NOT EXISTS importance ON memory TYPE int DEFAULT 5 ASSERT \$value >= 0 AND \$value <= 10;",
            "DEFINE FIELD IF NOT EXISTS category ON memory TYPE string;",
            "DEFINE FIELD IF NOT EXISTS source_message ON memory TYPE option<record<message>>;",
            "DEFINE FIELD IF NOT EXISTS embedding ON memory TYPE option<array<float>>;",
            "DEFINE FIELD IF NOT EXISTS last_used ON memory TYPE datetime DEFAULT time::now();",
            "DEFINE FIELD IF NOT EXISTS created_at ON memory TYPE datetime DEFAULT time::now();",
            "DEFINE INDEX IF NOT EXISTS idx_memory_user ON memory COLUMNS user_id;",
            "DEFINE INDEX IF NOT EXISTS idx_memory_importance ON memory COLUMNS importance;",
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
