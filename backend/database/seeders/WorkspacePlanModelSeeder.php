<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\WorkspacePlan;
use App\Models\AiModel;


class WorkspacePlanModelSeeder extends Seeder
{


    public function run(): void
    {


        $models = [

            'qwen' =>
                AiModel::where(
                    'key',
                    'qwen-3'
                )->first(),


            'gpt' =>
                AiModel::where(
                    'key',
                    'gpt-5'
                )->first(),


            'deepseek' =>
                AiModel::where(
                    'key',
                    'deepseek-chat'
                )->first(),


        ];






        $plans = [


            'personal-basic' => [

                'qwen'

            ],



            'general-basic' => [

                'qwen'

            ],



            'general-pro' => [

                'qwen',

                'gpt'

            ],



            'general-advanced' => [

                'qwen',

                'gpt',

                'deepseek'

            ],




            'student-basic' => [

                'qwen'

            ],




            'student-pro' => [

                'qwen',

                'deepseek'

            ],




            'student-advanced' => [

                'qwen',

                'gpt',

                'deepseek'

            ],


        ];









        foreach($plans as $planName => $modelKeys)
        {


            $plan = WorkspacePlan::where(

                'name',

                $planName

            )->first();



            if(!$plan){

                $this->command->warn(
                    "Plan not found: {$planName}"
                );

                continue;

            }






            // جلوگیری از duplicate pivot

            $plan
                ->models()
                ->detach();







            foreach($modelKeys as $index => $key)
            {


                $model = $models[$key] ?? null;



                if(!$model){

                    $this->command->warn(
                        "Model not found: {$key}"
                    );

                    continue;

                }






                $plan
                    ->models()
                    ->attach(

                        $model->id,

                        [

                            'is_default' =>
                                $index === 0

                        ]

                    );






                $this->command->info(

                    "{$planName} -> {$model->key}"

                );


            }


        }






        $this->command->info(
            '✅ Workspace Plan Models seeded successfully'
        );


    }


}
