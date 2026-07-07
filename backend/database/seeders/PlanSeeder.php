<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // ۱. پلن رایگان (پیش‌فرض)
        Plan::updateOrCreate(
            ['name' => 'free'],
            [
                'display_name' => 'رایگان',
                'model' => 'gpt-4o-mini',
                'price' => 0,
                'duration_days' => 365, // یک سال
                'daily_message_limit' => 4,
                'features' => [
                    'روزانه ۴ پیام رایگان',
                    'مدل GPT-4o-mini',
                    'مناسب برای استفاده‌ی اولیه',
                ],
                'is_active' => true,
            ]
        );

        // ۲. پلن پایه
        Plan::updateOrCreate(
            ['name' => 'basic'],
            [
                'display_name' => 'پایه',
                'model' => 'gpt-4o-mini',
                'price' => 100000,
                'duration_days' => 30,
                'daily_message_limit' => 50,
                'features' => [
                    'روزانه ۵۰ پیام',
                    'مدل GPT-4o-mini',
                    'پاسخ‌های سریع',
                ],
                'is_active' => true,
            ]
        );

        // ۳. پلن متوسط
        Plan::updateOrCreate(
            ['name' => 'standard'],
            [
                'display_name' => 'متوسط',
                'model' => 'gpt-4o',
                'price' => 250000,
                'duration_days' => 30,
                'daily_message_limit' => 200,
                'features' => [
                    'روزانه ۲۰۰ پیام',
                    'مدل GPT-4o',
                    'پاسخ‌های دقیق‌تر',
                ],
                'is_active' => true,
            ]
        );

        // ۴. پلن پیشرفته
        Plan::updateOrCreate(
            ['name' => 'premium'],
            [
                'display_name' => 'پیشرفته',
                'model' => 'gpt-4.5',
                'price' => 450000,
                'duration_days' => 30,
                'daily_message_limit' => 500,
                'features' => [
                    'روزانه ۵۰۰ پیام',
                    'مدل GPT-4.5',
                    'پاسخ‌های هوشمندانه',
                ],
                'is_active' => true,
            ]
        );

        // ۵. پلن ویژه
        Plan::updateOrCreate(
            ['name' => 'enterprise'],
            [
                'display_name' => 'ویژه',
                'model' => 'gpt-4-turbo',
                'price' => 750000,
                'duration_days' => 30,
                'daily_message_limit' => 1000,
                'features' => [
                    'روزانه ۱۰۰۰ پیام',
                    'مدل GPT-4-Turbo',
                    'بالاترین سرعت و دقت',
                ],
                'is_active' => true,
            ]
        );

        $this->command->info('✅ پلن‌ها با موفقیت ایجاد شدند.');
    }
}
