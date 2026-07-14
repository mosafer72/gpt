<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenWallet extends Model
{
    protected $fillable = [
        'user_id',
        'total_tokens',
        'used_tokens',
        'remaining_tokens',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
