<?php

namespace Database\Seeders;

use App\Models\Chat;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        // ایجاد ۱۰ چت با عنوان‌های تصادفی
        Chat::factory()->count(10)->create();
    }
}
