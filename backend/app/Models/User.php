<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'phone',
        'name',
        'verification_code',
        'verification_code_expires_at',
        'phone_verified_at',
        'role'
    ];

    protected $hidden = [
        'verification_code',
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
    ];

    // رابطه با اشتراک‌ها
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    // اشتراک فعال فعلی
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest('id');
    }

    // آیا اشتراک فعال دارد؟
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();
    }

    // متد currentPlan را به‌روز کنید
    public function currentPlan()
    {

        // اگر اشتراک فعال وجود دارد، پلن آن را برگردان
        $subscription = $this->activeSubscription()->with('plan')->first();
        if ($subscription && $subscription->isValid()) {
            return $subscription->plan;
        }

        // در غیر این صورت، پلن رایگان را برگردان
        return Plan::where('name', 'free')->first();
    }


    // متد جدید برای شمارش پیام‌های امروز کاربر
    public function todayMessagesCount(): int
    {
        return $this->messages()
            ->whereDate('created_at', today())
            ->count();
    }

    // متد برای بررسی اینکه آیا کاربر می‌تواند پیام جدید ارسال کند
    public function canSendMessage(): bool
    {
        $limit = $this->dailyMessageLimit();
        $todayCount = $this->todayMessagesCount();
        return $todayCount < $limit;
    }
    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }
    public function dailyMessageLimit(): int
    {
        $plan = $this->currentPlan();
        return $plan ? $plan->daily_message_limit : 4; // پیش‌فرض ۴ پیام
    }

    public function tokenWallet()
    {
        return $this->hasOne(TokenWallet::class);
    }
    public function availableModels()
    {

        $plan = $this->currentPlan();


        if (!$plan) {

            return collect([]);

        }


        return $plan->models;

    }
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('is_active', true)
            ->latest();
    }
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
