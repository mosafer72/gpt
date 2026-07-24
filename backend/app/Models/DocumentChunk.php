<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class DocumentChunk extends Model
{


    protected $fillable=[

        'document_id',

        'content',

        'chunk_index',

        'tokens'

    ];



    public function document()
    {

        return $this->belongsTo(
            Document::class
        );

    }


}
