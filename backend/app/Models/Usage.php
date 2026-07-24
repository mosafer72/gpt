<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Usage extends Model
{

    protected $fillable = [


        'workspace_id',

        'user_id',

        'ai_model_id',

        'provider',

        'model',

        'input_tokens',

        'output_tokens',

        'total_tokens',

        'input_cost',

        'output_cost',

        'total_cost',

        'status',

        'metadata'

    ];


    protected $casts = [

        'metadata' => 'array'


    ];



    public function workspace()
    {
        return $this->belongsTo(
            Workspace::class
        );
    }



    public function conversation()
    {
        return $this->belongsTo(
            Chat::class
        );
    }


}
