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
                LlmProvider::where(
                    'key',
                    'openai'
                )->first(),


            'openrouter' =>
                LlmProvider::where(
                    'key',
                    'openrouter'
                )->first(),


            'deepseek' =>
                LlmProvider::where(
                    'key',
                    'deepseek'
                )->first(),


            'anthropic' =>
                LlmProvider::where(
                    'key',
                    'anthropic'
                )->first(),


            'qwen' =>
                LlmProvider::where(
                    'key',
                    'qwen'
                )->first(),

        ];






        $models = [


            [
                'key'=>'gpt-4.1-mini',

                'name'=>'GPT 4.1 Mini',

                'model_name'=>'gpt-4.1-mini',

                'provider'=>'openai',

                'type'=>'chat'

            ],




            [
                'key'=>'gpt-5',

                'name'=>'GPT 5',

                'model_name'=>'gpt-5',

                'provider'=>'openai',

                'type'=>'chat'

            ],





            [
                'key'=>'qwen2.5-64k:latest',

                'name'=>'Qwen 2.5 64K',

                'model_name'=>'qwen2.5-64k:latest',

                'provider'=>'openrouter',

                'type'=>'chat'

            ],





            [
                'key'=>'deepseek-chat',

                'name'=>'DeepSeek Chat',

                'model_name'=>'deepseek-chat',

                'provider'=>'deepseek',

                'type'=>'chat'

            ],





            [
                'key'=>'claude-sonnet-4',

                'name'=>'Claude Sonnet 4',

                'model_name'=>'claude-sonnet-4',

                'provider'=>'anthropic',

                'type'=>'chat'

            ],





            [
                'key'=>'qwen-3',

                'name'=>'Qwen 3',

                'model_name'=>'qwen-3',

                'provider'=>'qwen',

                'type'=>'chat'

            ],


        ];







        foreach($models as $model)
        {


            $provider =
                $providers[$model['provider']] ?? null;



            if(!$provider){

                continue;

            }




            AiModel::updateOrCreate(

                [

                    'key'=>$model['key']

                ],

                [

                    'name'=>$model['name'],

                    'model_name'=>$model['model_name'],

                    'provider_id'=>$provider->id,

                    'input_cost'=>0,

                    'output_cost'=>0,

                    'type'=>$model['type'],

                    'is_active'=>true

                ]

            );


        }






        $this->command->info(
            '✅ AI Models seeded successfully'
        );


    }

}
