<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {

        Plan::updateOrCreate(
            ['name' => 'free'],
            [
                'display_name' => 'رایگان',

                'model' => 'gpt-4o-mini',

                'price' => 0,

                'duration_days' => 365,

                'daily_message_limit' => 4,

                'features' => [
                    'روزانه ۴ پیام رایگان',
                    'مدل GPT-4o-mini',
                    'بدون حافظه و Planner'
                ],

                'capabilities' => [
                    'planner' => false,
                    'memory' => false,
                    'max_tokens' => 1000,
                    'temperature' => 0.7
                ],

                'is_active' => true,
            ]
        );



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
                    'حافظه گفتگو فعال'
                ],

                'capabilities' => [
                    'planner' => false,
                    'memory' => true,
                    'max_tokens' => 700,
                    'temperature' => 0.7
                ],

                'is_active' => true,
            ]
        );



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
                    'Planner فعال',
                    'حافظه بلندمدت',
                    'مدل GPT-4o'
                ],

                'capabilities' => [
                    'planner' => true,
                    'memory' => true,
                    'max_tokens' => 4000,
                    'temperature' => 0.7
                ],

                'is_active' => true,
            ]
        );



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
                    'Planner پیشرفته',
                    'حافظه هوشمند',
                    'مدل GPT-4.5'
                ],

                'capabilities' => [
                    'planner' => true,
                    'memory' => true,
                    'max_tokens' => 8000,
                    'temperature' => 0.6
                ],

                'is_active' => true,
            ]
        );



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
                    'تمام قابلیت‌های هوشمند فعال',
                    'بالاترین کیفیت پاسخ'
                ],

                'capabilities' => [
                    'planner' => true,
                    'memory' => true,
                    'max_tokens' => 16000,
                    'temperature' => 0.5
                ],

                'is_active' => true,
            ]
        );


        $this->command->info('✅ پلن‌ها با موفقیت ایجاد شدند.');
    }
}
