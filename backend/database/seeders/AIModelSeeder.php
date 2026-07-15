<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\AiModel;
use App\Models\LLMProvider;



class AiModelSeeder extends Seeder
{

    public function run(): void
    {


        $openai =
            LLMProvider::where('key','openai')
            ->first();



        $openrouter =
            LLMProvider::where('key','openrouter')
            ->first();



        $deepseek =
            LLMProvider::where('key','deepseek')
            ->first();



        $anthropic =
            LLMProvider::where('key','anthropic')
            ->first();



        $qwen =
            LLMProvider::where('key','qwen')
            ->first();







        AiModel::updateOrCreate(

            [
                'key'=>'gpt-4.1-mini'
            ],

            [

                'name'=>'GPT 4.1 Mini',

                'model_name'=>'gpt-4.1-mini',

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

                'model_name'=>'gpt-5',

                'provider_id'=>$openai->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );








        AiModel::updateOrCreate(

            [
                'key'=>'oss-120b'
            ],

            [

                'name'=>'GPT OSS 120B Free',

                'model_name'=>'openai/gpt-oss-120b:free',

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
