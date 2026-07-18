<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paper extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'path',
        'url',
        'mime_type',
        'size',
        'extracted_text',
        'summary',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chats()
    {
        return $this->belongsToMany(
            Chat::class,
            'chat_paper'
        )->withTimestamps();
    }
}
