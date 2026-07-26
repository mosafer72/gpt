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
            LlmProviderSeeder::class,
            AiModelSeeder::class,
            WorkspaceTypeSeeder::class,
            WorkspacePlanSeeder::class,
            WorkspaceSeeder::class,
            ChatSeeder::class,              // ۱۰ چت با پیام‌های محدود
            ChatWithMessagesSeeder::class,  // چت طولانی + ۵ چت متوسط
            WorkspaceSeeder::class,
            UserSeeder::class,  // چت طولانی + ۵ چت متوسط
            WorkspacePlanModelSeeder::class,
        ]);
    }
}
