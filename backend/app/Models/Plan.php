<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

protected $fillable = [
    'name',
    'display_name',
    'model',
    'price',
    'duration_days',
    'daily_message_limit', // ← اضافه کنید
    'features',
    'is_active',
];

protected $casts = [
    'features' => 'array',
    'price' => 'integer',
    'daily_message_limit' => 'integer',
    'is_active' => 'boolean',
];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // کمترین قیمت به تومان (برای نمایش)
    public function getPriceInTomansAttribute(): int
    {
        return (int) ($this->price / 10);
    }
}
