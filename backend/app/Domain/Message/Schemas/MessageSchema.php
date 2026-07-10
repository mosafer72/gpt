<?php

namespace App\Domain\Message\Schemas;

use Surqlize\Model\SchemaContract;

class MessageSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            "DEFINE TABLE IF NOT EXISTS message SCHEMAFULL;",
            "DEFINE FIELD IF NOT EXISTS conversation ON message TYPE record<conversation>;",
            "DEFINE FIELD IF NOT EXISTS role ON message TYPE string ASSERT \$value IN ['user', 'assistant', 'system'];",
            "DEFINE FIELD IF NOT EXISTS content ON message TYPE string;",
            "DEFINE FIELD IF NOT EXISTS token_count ON message TYPE int;",
            "DEFINE FIELD IF NOT EXISTS metadata ON message TYPE object;",
            "DEFINE FIELD IF NOT EXISTS created_at ON message TYPE datetime DEFAULT time::now();",
            "DEFINE INDEX IF NOT EXISTS idx_message_conversation ON message COLUMNS conversation;",
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
