<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'started_at',
        'expires_at',
        'is_active',
        'transaction_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // آیا اشتراک فعال است؟
    public function isValid(): bool
    {
        return $this->is_active && $this->expires_at && $this->expires_at->isFuture();
    }

    // روزهای باقی‌مانده
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at) return 0;
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    // قیمت تناسبی برای ارتقا (بر اساس روزهای باقی‌مانده)
    public function getProRatedPrice(Plan $newPlan): int
    {
        if (!$this->isValid()) {
            return $newPlan->price;
        }

        $totalDays = $this->plan->duration_days;
        $daysUsed = $totalDays - $this->days_remaining;
        $remainingRatio = $daysUsed / $totalDays;

        // قیمت پرداخت‌شده برای بسته فعلی
        $paidPrice = $this->plan->price;
        $usedValue = $paidPrice * $remainingRatio;

        // اعتبار باقی‌مانده
        $remainingCredit = $paidPrice - $usedValue;

        // قیمت جدید منهای اعتبار باقی‌مانده (حداقل ۰)
        return max(0, $newPlan->price - $remainingCredit);
    }
}
