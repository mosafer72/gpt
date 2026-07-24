<?php

namespace App\Application\Documents;

use SurrealDB\Laravel\Facades\SurrealDB;




class SurrealDocumentStorage
{


    public function saveChunk(
        string $documentId,
        int $index,
        string $content
    )
    {


        return SurrealDB::create(
            'document_chunk',
            [

                'document_id'=>$documentId,

                'chunk_index'=>$index,

                'content'=>$content,

                'tokens'=>
                    intval(strlen($content)/4)

            ]
        );


    }



    public function saveEmbedding(
        string $documentId,
        string $chunkId,
        array $vector,
        string $content
    )
    {


        return Surreal::create(
            'document_embedding',
            [

                'document_id'=>$documentId,

                'chunk_id'=>$chunkId,

                'content'=>$content,

                'vector'=>$vector

            ]
        );


    }


}
