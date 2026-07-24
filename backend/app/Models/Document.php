<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Document extends Model
{


    protected $fillable = [

        'workspace_id',

        'user_id',

        'name',

        'path',

        'mime_type',

        'size',

        'status',

        'error'

    ];



    public function workspace()
    {

        return $this->belongsTo(
            Workspace::class
        );

    }



    public function user()
    {

        return $this->belongsTo(
            User::class
        );

    }

    public function chunks()
    {
        return $this->hasMany(
            DocumentChunk::class
        );
    }
}
