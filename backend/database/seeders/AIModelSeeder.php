<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\AiModel;
use App\Models\LlmProvider;



class AiModelSeeder extends Seeder
{

    public function run(): void
    {


        $openai =
            LlmProvider::where('key','openai')
            ->first();



        $openrouter =
            LlmProvider::where('key','openrouter')
            ->first();



        $deepseek =
            LlmProvider::where('key','deepseek')
            ->first();



        $anthropic =
            LlmProvider::where('key','anthropic')
            ->first();



        $qwen =
            LlmProvider::where('key','qwen')
            ->first();







        AiModel::updateOrCreate(

            [
                'key'=>'gpt-4.1-mini'
            ],

            [

                'name'=>'GPT 4.1 Mini',

                'model_name'=>'gapgpt-qwen-3.6',

                'provider_id'=>$openai->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );







        AiModel::updateOrCreate(

            [
                'key'=>'gpt-5'
            ],

            [

                'name'=>'GPT 5',

                'model_name'=>'gapgpt-qwen-3.6',

                'provider_id'=>$openai->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );








        AiModel::updateOrCreate(

            [
                'key'=>'gapgpt-qwen-3.6'
            ],

            [

                'name'=>'GPT OSS 120B Free',

                'model_name'=>'gapgpt-qwen-3.6',

                'provider_id'=>$openrouter->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );







        AiModel::updateOrCreate(

            [
                'key'=>'deepseek-chat'
            ],

            [

                'name'=>'DeepSeek Chat',

                'model_name'=>'deepseek-chat',

                'provider_id'=>$deepseek->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );








        AiModel::updateOrCreate(

            [
                'key'=>'claude-sonnet-4'
            ],

            [

                'name'=>'Claude Sonnet 4',

                'model_name'=>'claude-sonnet-4',

                'provider_id'=>$anthropic->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );








        AiModel::updateOrCreate(

            [
                'key'=>'qwen-3'
            ],

            [

                'name'=>'Qwen 3',

                'model_name'=>'qwen-3',

                'provider_id'=>$qwen->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );


    }

}
