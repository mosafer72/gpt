<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIModel;

class AIModelSeeder extends Seeder
{

    public function run(): void
    {


        AIModel::updateOrCreate(
            [
                'key'=>'openai/gpt-oss-120b:free'
            ],
            [
                'name'=>'GPT OSS 120B Free',
                'provider'=>'openrouter',
                'input_cost'=>0,
                'output_cost'=>0,
            ]
        );


        AIModel::updateOrCreate(
            [
                'key'=>'gpt-4.1-mini'
            ],
            [
                'name'=>'GPT 4.1 Mini',
                'provider'=>'openai',
                'input_cost'=>0,
                'output_cost'=>0,
            ]
        );



        AIModel::updateOrCreate(
            [
                'key'=>'gpt-5'
            ],
            [
                'name'=>'GPT 5',
                'provider'=>'openai',
                'input_cost'=>0,
                'output_cost'=>0,
            ]
        );



        AIModel::updateOrCreate(
            [
                'key'=>'deepseek-chat'
            ],
            [
                'name'=>'DeepSeek Chat',
                'provider'=>'deepseek',
                'input_cost'=>0,
                'output_cost'=>0,
            ]
        );



        AIModel::updateOrCreate(
            [
                'key'=>'claude-sonnet-4'
            ],
            [
                'name'=>'Claude Sonnet 4',
                'provider'=>'anthropic',
                'input_cost'=>0,
                'output_cost'=>0,
            ]
        );



        AIModel::updateOrCreate(
            [
                'key'=>'qwen-3'
            ],
            [
                'name'=>'Qwen 3',
                'provider'=>'qwen',
                'input_cost'=>0,
                'output_cost'=>0,
            ]
        );


    }

}
