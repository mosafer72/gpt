<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $freePlan = Plan::where('name', 'free')->first();

        if (!$freePlan) {
            $this->command->error('❌ پلن رایگان پیدا نشد! لطفاً ابتدا PlanSeeder را اجرا کنید.');
            return;
        }

        // ۱. کاربر اصلی (شماره شما)
        $user1 = User::updateOrCreate(
            ['phone' => '09190941931'],
            [
                'name' => 'علی رضایی',
                'verification_code' => Hash::make('123456'),
                'verification_code_expires_at' => now()->addMinutes(5),
                'phone_verified_at' => now(),
            ]
        );

        // اختصاص اشتراک رایگان به کاربر اول
        Subscription::updateOrCreate(
            ['user_id' => $user1->id, 'plan_id' => $freePlan->id],
            [
                'started_at' => now(),
                'expires_at' => now()->addDays($freePlan->duration_days),
                'is_active' => true,
                'transaction_id' => 'free_' . time(),
            ]
        );

        // ۲. کاربر تستی دوم
        $user2 = User::updateOrCreate(
            ['phone' => '09123456789'],
            [
                'name' => 'کاربر تست',
                'verification_code' => Hash::make('654321'),
                'verification_code_expires_at' => now()->addMinutes(5),
                'phone_verified_at' => now(),
            ]
        );

        Subscription::updateOrCreate(
            ['user_id' => $user2->id, 'plan_id' => $freePlan->id],
            [
                'started_at' => now(),
                'expires_at' => now()->addDays($freePlan->duration_days),
                'is_active' => true,
                'transaction_id' => 'free_' . time(),
            ]
        );

        $this->command->info('✅ کاربران با اشتراک رایگان ایجاد شدند.');
        $this->command->info('📱 شماره شما: 09190941931 (کد: 123456)');
        $this->command->info('📱 شماره تست: 09123456789 (کد: 654321)');
        $this->command->info('📊 محدودیت روزانه: ۴ پیام');
    }
}
