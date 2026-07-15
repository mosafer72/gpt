<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

use App\Models\Plan;
use App\Models\AiModel;



class PlanSeeder extends Seeder
{


    public function run(): void
    {






        $free = Plan::updateOrCreate(

            [
                'name'=>'free'
            ],

            [

                'display_name'=>'رایگان',

                'price'=>0,

                'token_limit'=>50000,

                'duration_days'=>365,

                'daily_message_limit'=>4,


                'features'=>[
                    'روزانه ۴ پیام رایگان',
                    'مدل رایگان'
                ],


                'capabilities'=>[

                    'planner'=>false,

                    'memory'=>false,

                    'max_tokens'=>1000,

                    'temperature'=>0.7

                ],


                'is_active'=>true

            ]

        );







        $basic = Plan::updateOrCreate(

            [
                'name'=>'basic'
            ],

            [

                'display_name'=>'پایه',

                'price'=>100000,

                'token_limit'=>1000000,

                'duration_days'=>30,

                'daily_message_limit'=>50,


                'features'=>[
                    'حافظه گفتگو',
                    'مدل های پایه'
                ],


                'capabilities'=>[

                    'planner'=>false,

                    'memory'=>true,

                    'max_tokens'=>2000,

                    'temperature'=>0.7

                ],


                'is_active'=>true

            ]

        );







        $standard = Plan::updateOrCreate(

            [
                'name'=>'standard'
            ],

            [

                'display_name'=>'متوسط',

                'price'=>250000,

                'token_limit'=>3000000,

                'duration_days'=>30,

                'daily_message_limit'=>200,


                'features'=>[
                    'Planner',
                    'Memory',
                    'مدل های حرفه ای'
                ],


                'capabilities'=>[

                    'planner'=>true,

                    'memory'=>true,

                    'max_tokens'=>4000,

                    'temperature'=>0.7

                ],


                'is_active'=>true

            ]

        );







        $premium = Plan::updateOrCreate(

            [
                'name'=>'premium'
            ],

            [

                'display_name'=>'پیشرفته',

                'price'=>450000,

                'token_limit'=>10000000,

                'duration_days'=>30,

                'daily_message_limit'=>500,


                'features'=>[
                    'تمام مدل ها',
                    'Planner پیشرفته',
                    'Memory هوشمند'
                ],


                'capabilities'=>[

                    'planner'=>true,

                    'memory'=>true,

                    'max_tokens'=>8000,

                    'temperature'=>0.6

                ],


                'is_active'=>true

            ]

        );








        $enterprise = Plan::updateOrCreate(

            [
                'name'=>'enterprise'
            ],

            [

                'display_name'=>'ویژه',

                'price'=>750000,

                'token_limit'=>30000000,

                'duration_days'=>30,

                'daily_message_limit'=>1000,


                'features'=>[
                    'تمام مدل ها',
                    'بالاترین کیفیت'
                ],


                'capabilities'=>[

                    'planner'=>true,

                    'memory'=>true,

                    'max_tokens'=>16000,

                    'temperature'=>0.5

                ],


                'is_active'=>true

            ]

        );








        /*
        |--------------------------------------------------------------------------
        | Attach Models
        |--------------------------------------------------------------------------
        */


        $models = AiModel::pluck('id','key');





        $free->models()->sync([

            $models['gapgpt-qwen-3.6'] => [

                'is_default'=>true

            ]

        ]);







        $basic->models()->sync([

            $models['gapgpt-qwen-3.6']=>[

                'is_default'=>true

            ],


            $models['deepseek-chat']=>[]

        ]);







        $standard->models()->sync([

            $models['gpt-4.1-mini']=>[

                'is_default'=>true

            ],


            $models['deepseek-chat']=>[],

            $models['qwen-3']=>[]

        ]);








        $premium->models()->sync([

            $models['gpt-5']=>[

                'is_default'=>true

            ],


            $models['claude-sonnet-4']=>[],


            $models['qwen-3']=>[],


            $models['deepseek-chat']=>[]

        ]);







        $enterprise->models()->sync([

            $models['gpt-5']=>[

                'is_default'=>true

            ],


            $models['claude-sonnet-4']=>[],


            $models['gpt-4.1-mini']=>[]

        ]);







        $this->command->info(
            '✅ پلن‌ها و مدل‌ها با موفقیت ساخته شدند'
        );


    }

}
