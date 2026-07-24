<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\AiModel;
use App\Models\LlmProvider;



class AiModelSeeder extends Seeder
{

    public function run(): void
    {


        $providers = [

            'openai' =>
                LlmProvider::where('key','openai')->first(),

            'openrouter' =>
                LlmProvider::where('key','openrouter')->first(),

            'deepseek' =>
                LlmProvider::where('key','deepseek')->first(),

            'anthropic' =>
                LlmProvider::where('key','anthropic')->first(),

            'qwen' =>
                LlmProvider::where('key','qwen')->first(),

        ];




        /*
        |--------------------------------------------------------------------------
        | OpenAI Models
        |--------------------------------------------------------------------------
        */


        AiModel::updateOrCreate(

            [
                'key'=>'gpt-4.1-mini'
            ],

            [

                'name'=>'GPT 4.1 Mini',

                'model_name'=>'gpt-4.1-mini',

                'provider_id'=>$providers['openai']->id,

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

                'provider_id'=>$providers['openai']->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );







        /*
        |--------------------------------------------------------------------------
        | OpenRouter / GapGPT
        |--------------------------------------------------------------------------
        */


        AiModel::updateOrCreate(

            [
                'key'=>'gapgpt-qwen-3.6'
            ],

            [

                'name'=>'GapGPT Qwen 3.6',

                'model_name'=>'gapgpt-qwen-3.6',

                'provider_id'=>$providers['openrouter']->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );







        /*
        |--------------------------------------------------------------------------
        | DeepSeek
        |--------------------------------------------------------------------------
        */


        AiModel::updateOrCreate(

            [
                'key'=>'deepseek-chat'
            ],

            [

                'name'=>'DeepSeek Chat',

                'model_name'=>'deepseek-chat',

                'provider_id'=>$providers['deepseek']->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );







        /*
        |--------------------------------------------------------------------------
        | Anthropic
        |--------------------------------------------------------------------------
        */


        AiModel::updateOrCreate(

            [
                'key'=>'claude-sonnet-4'
            ],

            [

                'name'=>'Claude Sonnet 4',

                'model_name'=>'claude-sonnet-4',

                'provider_id'=>$providers['anthropic']->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );







        /*
        |--------------------------------------------------------------------------
        | Qwen
        |--------------------------------------------------------------------------
        */


        AiModel::updateOrCreate(

            [
                'key'=>'qwen-3'
            ],

            [

                'name'=>'Qwen 3',

                'model_name'=>'qwen-3',

                'provider_id'=>$providers['qwen']->id,

                'input_cost'=>0,

                'output_cost'=>0,

                'is_active'=>true,

            ]

        );



        $this->command->info(
            '✅ AI Models seeded successfully'
        );


    }

}
