<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usage extends Model
{

    protected $fillable = [

        'user_id',

        'conversation_id',

        'model',

        'prompt_tokens',

        'completion_tokens',

        'total_tokens',

    ];

    protected $casts = [

        'prompt_tokens' => 'integer',

        'completion_tokens' => 'integer',

        'total_tokens' => 'integer',

    ];

    public function user(): BelongsTo
    {

        return $this->belongsTo(User::class);

    }

    public function conversation(): BelongsTo
    {

        return $this->belongsTo(Chat::class);

    }

}

