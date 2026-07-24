<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\AiModel;


class PlanSeeder extends Seeder
{

    public function run(): void
    {

        $models = AiModel::pluck(
            'id',
            'key'
        );


        /*
        |--------------------------------------------------------------------------
        | Free Chat
        |--------------------------------------------------------------------------
        */


        $free = Plan::updateOrCreate(

            [
                'name'=>'free'
            ],

            [

                'display_name'=>'Free',

                'price'=>0,

                'token_limit'=>50000,

                'duration_days'=>365,

                'daily_message_limit'=>10,


                'features'=>[

                    'چت عمومی',

                    'مدل رایگان',

                    'حافظه ساده'

                ],


                'capabilities'=>[

                    'memory'=>true,

                    'planner'=>false,

                    'file_upload'=>false,

                    'image_generation'=>false,

                    'max_tokens'=>1000

                ],


                'is_active'=>true

            ]

        );



        /*
        |--------------------------------------------------------------------------
        | Pro Chat
        |--------------------------------------------------------------------------
        */


        $pro = Plan::updateOrCreate(

            [
                'name'=>'pro'
            ],

            [

                'display_name'=>'AI Pro',

                'price'=>199000,

                'token_limit'=>1000000,

                'duration_days'=>30,

                'daily_message_limit'=>300,


                'features'=>[

                    'مدل‌های قوی‌تر',

                    'Memory',

                    'File Upload',

                    'Reasoning'

                ],


                'capabilities'=>[

                    'memory'=>true,

                    'planner'=>true,

                    'file_upload'=>true,

                    'advanced_reasoning'=>true,

                    'max_tokens'=>6000

                ],


                'is_active'=>true

            ]

        );




        /*
        |--------------------------------------------------------------------------
        | Attach Models
        |--------------------------------------------------------------------------
        */


        $free->models()->sync([

            $models['gapgpt-qwen-3.6'] => [

                'is_default'=>true

            ]

        ]);



        $pro->models()->sync([


            $models['gpt-4.1-mini'] => [

                'is_default'=>true

            ],


            $models['deepseek-chat'] => [],


            $models['qwen-3'] => [],


        ]);



        $this->command->info(
            '✅ General chat plans created'
        );

    }

}
