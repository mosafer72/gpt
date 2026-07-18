<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\AiModel;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $models = AiModel::pluck('id', 'key');

        /*
        |--------------------------------------------------------------------------
        | Starter (Free)
        |--------------------------------------------------------------------------
        */
        $starter = Plan::updateOrCreate(
            ['name' => 'starter'],
            [
                'display_name' => 'Starter',
                'price' => 0,
                'token_limit' => 50000,
                'duration_days' => 365,
                'daily_message_limit' => 10,

                'features' => [
                    'چت پایه',
                    'حافظه ساده',
                    'مدل رایگان'
                ],

                'capabilities' => [
                    'memory' => true,
                    'planner' => false,
                    'image_generation' => false,
                    'voice_input' => false,
                    'voice_output' => false,
                    'web_search' => false,
                    'file_upload' => false,
                    'code_mode' => false,
                    'advanced_reasoning' => false,
                    'custom_prompt' => false,
                    'max_tokens' => 1000,
                    'temperature' => 0.7,
                    'ui_theme' => 'free',
                ],

                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Developer
        |--------------------------------------------------------------------------
        */
        $developer = Plan::updateOrCreate(
            ['name' => 'developer'],
            [
                'display_name' => 'Developer',
                'price' => 299000,
                'token_limit' => 1000000,
                'duration_days' => 30,
                'daily_message_limit' => 500,

                'features' => [
                    'تحلیل کد',
                    'آپلود فایل',
                    'جستجوی وب',
                    'Planner',
                    'Memory هوشمند'
                ],

                'capabilities' => [
                    'memory' => true,
                    'planner' => true,
                    'image_generation' => false,
                    'voice_input' => false,
                    'voice_output' => false,
                    'web_search' => true,
                    'file_upload' => true,
                    'code_mode' => true,
                    'advanced_reasoning' => true,
                    'custom_prompt' => false,
                    'max_tokens' => 4000,
                    'temperature' => 0.7,
                    'ui_theme' => 'developer',
                ],

                'is_active' => true,
            ]
        );
        $student = Plan::updateOrCreate(
            ['name' => 'student'],
            [
                'display_name' => 'Student / Researcher',
                'price' => 199000,
                'token_limit' => 2000000,
                'duration_days' => 30,
                'daily_message_limit' => 300,

                'features' => [
                    'آپلود PDF مقاله',
                    'ترجمه علمی',
                    'خلاصه بخش‌های مقاله',
                    'مقایسه چند مقاله',
                    'نتیجه‌گیری پژوهشی',
                    'پیشنهاد موضوع تحقیق'
                ],

                'capabilities' => [
                    'memory' => true,
                    'planner' => true,
                    'file_upload' => true,
                    'pdf_reader' => true,
                    'paper_translate' => true,
                    'paper_summary' => true,
                    'paper_compare' => true,
                    'research_conclusion' => true,
                    'citation_helper' => true,
                    'max_tokens' => 8000,
                    'temperature' => 0.4,
                    'ui_theme' => 'student',
                ],

                'is_active' => true,
            ]
        );
        /*
        |--------------------------------------------------------------------------
        | Creator
        |--------------------------------------------------------------------------
        */
        $creator = Plan::updateOrCreate(
            ['name' => 'creator'],
            [
                'display_name' => 'Creator',
                'price' => 399000,
                'token_limit' => 3000000,
                'duration_days' => 30,
                'daily_message_limit' => 500,

                'features' => [
                    'تولید تصویر',
                    'SEO',
                    'خروجی PDF',
                    'Planner',
                    'Memory'
                ],

                'capabilities' => [
                    'memory' => true,
                    'planner' => true,
                    'image_generation' => true,
                    'voice_input' => false,
                    'voice_output' => false,
                    'web_search' => true,
                    'file_upload' => true,
                    'code_mode' => false,
                    'advanced_reasoning' => false,
                    'custom_prompt' => false,
                    'max_tokens' => 8000,
                    'temperature' => 0.7,
                    'ui_theme' => 'creator',
                ],

                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Attach Models
        |--------------------------------------------------------------------------
        */

        $starter->models()->sync([
            $models['gapgpt-qwen-3.6'] => ['is_default' => true],
        ]);

        $developer->models()->sync([
            $models['gpt-4.1-mini'] => ['is_default' => true],
            $models['deepseek-chat'] => [],
            $models['qwen-3'] => [],
        ]);

        $creator->models()->sync([
            $models['gpt-5'] => ['is_default' => true],
            $models['claude-sonnet-4'] => [],
            $models['gpt-4.1-mini'] => [],
        ]);
        $student->models()->sync([
            $models['gpt-4.1-mini'] => ['is_default' => true],
            $models['qwen-3'] => [],
            $models['deepseek-chat'] => [],
        ]);

        $this->command->info('✅ تخصصی‌ترین پلن‌ها با موفقیت ساخته شدند');
    }
}
