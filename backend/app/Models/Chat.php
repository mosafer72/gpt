<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'user_id', 'summery'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
    public function papers()
    {
        return $this->belongsToMany(
            Paper::class,
            'chat_paper'
        )->withTimestamps();
    }
}
