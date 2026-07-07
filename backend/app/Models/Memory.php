<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Memory extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر کردن
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'key',
        'value',
        'source',
        'last_used_at',
    ];

    /**
     * نوع داده‌های فیلدها
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * رابطه با کاربر
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * محدوده‌ی حافظه‌های اخیر (آخرین ۳۰ روز)
     */
    public function scopeRecent($query)
    {
        return $query->where('last_used_at', '>', now()->subDays(30));
    }

    /**
     * محدوده‌ی حافظه‌های فعال (با آخرین استفاده)
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('last_used_at');
    }

    /**
     * به‌روزرسانی زمان آخرین استفاده
     */
    public function touchLastUsed(): self
    {
        $this->update(['last_used_at' => now()]);
        return $this;
    }
}
