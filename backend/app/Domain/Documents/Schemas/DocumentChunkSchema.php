<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;

class DocumentChunkSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            "DEFINE TABLE IF NOT EXISTS document_chunk SCHEMAFULL;",

            "DEFINE FIELD IF NOT EXISTS document_id ON document_chunk TYPE string;",
            "DEFINE FIELD IF NOT EXISTS chunk_index ON document_chunk TYPE int;",
            "DEFINE FIELD IF NOT EXISTS content ON document_chunk TYPE string;",
            "DEFINE FIELD IF NOT EXISTS tokens ON document_chunk TYPE int;",
            "DEFINE FIELD IF NOT EXISTS created_at ON document_chunk TYPE datetime DEFAULT time::now();",

            // ایندکس حتماً بعد از تعریف فیلدها باشد
            "DEFINE INDEX IF NOT EXISTS idx_chunk_document ON document_chunk COLUMNS document_id;",
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
