<?php

namespace App\Domain\Documents\Schemas;

use Surqlize\Model\SchemaContract;

class DocumentVectorSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            "DEFINE TABLE IF NOT EXISTS document_vector SCHEMAFULL;",

            "DEFINE FIELD IF NOT EXISTS chunk_id ON document_vector TYPE string;",
            "DEFINE FIELD IF NOT EXISTS document_id ON document_vector TYPE string;",
            "DEFINE FIELD IF NOT EXISTS workspace_id ON document_vector TYPE string;",
            "DEFINE FIELD IF NOT EXISTS content ON document_vector TYPE string;",
            "DEFINE FIELD IF NOT EXISTS embedding ON document_vector TYPE array;",
            "DEFINE FIELD IF NOT EXISTS metadata ON document_vector TYPE object FLEXIBLE;",
            "DEFINE FIELD IF NOT EXISTS created_at ON document_vector TYPE datetime DEFAULT time::now();",
            "DEFINE FIELD IF NOT EXISTS updated_at ON document_vector TYPE datetime DEFAULT time::now();",

            // ایندکس‌ها در انتهای آرایه
            "DEFINE INDEX IF NOT EXISTS idx_document_vector_workspace ON document_vector COLUMNS workspace_id;",
            "DEFINE INDEX IF NOT EXISTS idx_document_vector_document ON document_vector COLUMNS document_id;",
            "DEFINE INDEX IF NOT EXISTS idx_document_vector_chunk ON document_vector COLUMNS chunk_id;",
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
