<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LlmProvider;


class LlmProviderSeeder extends Seeder
{

    public function run(): void
    {


        LlmProvider::updateOrCreate(

            [
                'key'=>'openai'
            ],

            [

                'name'=>'OpenAI',

                'api_key'=>env('OPENAI_API_KEY'),

                'base_url'=>'https://api.openai.com/v1',

                'is_active'=>true,

            ]

        );





        LlmProvider::updateOrCreate(

            [
                'key'=>'openrouter'
            ],

            [

                'name'=>'OpenRouter',

                'api_key'=>env('OPENROUTER_API_KEY'),

                'base_url'=>'https://openrouter.ai/api/v1',

                'is_active'=>true,

            ]

        );






        LlmProvider::updateOrCreate(

            [
                'key'=>'deepseek'
            ],

            [

                'name'=>'DeepSeek',

                'api_key'=>env('DEEPSEEK_API_KEY'),

                'base_url'=>'https://api.deepseek.com/v1',

                'is_active'=>true,

            ]

        );






        LlmProvider::updateOrCreate(

            [
                'key'=>'anthropic'
            ],

            [

                'name'=>'Anthropic Claude',

                'api_key'=>env('ANTHROPIC_API_KEY'),

                'base_url'=>'https://api.anthropic.com/v1',

                'is_active'=>true,

            ]

        );






        LlmProvider::updateOrCreate(

            [
                'key'=>'qwen'
            ],

            [

                'name'=>'Qwen',

                'api_key'=>env('QWEN_API_KEY'),

                'base_url'=>'https://dashscope.aliyuncs.com/compatible-mode/v1',

                'is_active'=>true,

            ]

        );


    }

}
