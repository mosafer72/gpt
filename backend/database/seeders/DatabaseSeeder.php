<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ChatSeeder::class,              // ۱۰ چت با پیام‌های محدود
            ChatWithMessagesSeeder::class,  // چت طولانی + ۵ چت متوسط
            AIModelSeeder::class,
            PlanSeeder::class,  // چت طولانی + ۵ چت متوسط
            UserSeeder::class,  // چت طولانی + ۵ چت متوسط
        ]);
    }
}
