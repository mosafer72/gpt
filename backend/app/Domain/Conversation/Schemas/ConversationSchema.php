<?php

namespace App\Domain\Conversation\Schemas;

use Surqlize\Model\SchemaContract;

class ConversationSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            "DEFINE TABLE IF NOT EXISTS conversation SCHEMAFULL;",
            "DEFINE FIELD IF NOT EXISTS user_id ON conversation TYPE string;",
            "DEFINE FIELD IF NOT EXISTS title ON conversation TYPE string;",
            "DEFINE FIELD IF NOT EXISTS model ON conversation TYPE string DEFAULT 'gpt-4o';",
            "DEFINE FIELD IF NOT EXISTS status ON conversation TYPE string DEFAULT 'active' ASSERT \$value IN ['active', 'archived', 'deleted'];",
            "DEFINE FIELD IF NOT EXISTS created_at ON conversation TYPE datetime DEFAULT time::now();",
            "DEFINE FIELD IF NOT EXISTS updated_at ON conversation TYPE datetime DEFAULT time::now();",
            "DEFINE INDEX IF NOT EXISTS idx_conversation_user ON conversation COLUMNS user_id;",
            "DEFINE INDEX IF NOT EXISTS idx_conversation_status ON conversation COLUMNS status;",
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
