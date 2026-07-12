<?php

namespace App\Models;

use App\Domain\Conversation\Models\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LLMRequest extends Model
{
    protected $table = 'llm_requests';


    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'conversation_id',
        'messages',
        'request_options',
        'response',
        'usage',
        'latency_ms',
        'status',
        'error',
    ];


    protected $casts = [

        'messages' => 'array',

        'request_options' => 'array',

        'response' => 'array',

        'usage' => 'array',

        'latency_ms' => 'integer',

    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function conversation()
    {
        return $this->belongsTo(
            Conversation::class,
            'conversation_id'
        );
    }


    public function scopeSuccessful($query)
    {
        return $query->where(
            'status',
            'success'
        );
    }


    public function scopeFailed($query)
    {
        return $query->where(
            'status',
            'failed'
        );
    }
}
