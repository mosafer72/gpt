<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceType;
use App\Models\AiModel;


class WorkspacePlanSeeder extends Seeder
{

    public function run(): void
    {


        $student =
            WorkspaceType::where(
                'name',
                'student'
            )->first();



        $studentPlan =
            WorkspacePlan::updateOrCreate(

                [
                    'name'=>'student-basic'
                ],

                [

                    'workspace_type_id'=>$student->id,

                    'display_name'=>'Student Basic',

                    'price'=>0,

                    'token_limit'=>50000,

                    'duration_days'=>365,


                    'features'=>[

                        'تحلیل PDF',

                        'خلاصه سازی',

                        'حل تمرین',

                        'تحقیق'

                    ]

                ]

            );





        $developer =
            WorkspaceType::where(
                'name',
                'developer'
            )->first();




        $developerPlan =
            WorkspacePlan::updateOrCreate(

                [

                    'name'=>'developer-pro'

                ],

                [

                    'workspace_type_id'=>$developer->id,

                    'display_name'=>'Developer Pro',

                    'price'=>199000,

                    'token_limit'=>200000,

                    'duration_days'=>30,


                    'features'=>[

                        'Code Review',

                        'Security Analysis',

                        'Debug'

                    ]

                ]

            );






        /*
        |--------------------------------------------------------------------------
        | Attach Models
        |--------------------------------------------------------------------------
        */


        $studentModels = AiModel::whereIn(

            'key',

            [

                'gpt-5-mini',

                'gemini-flash'

            ]

        )
        ->pluck('id');



        $developerModels = AiModel::whereIn(

            'key',

            [

                'gpt-5',

                'gpt-5-mini'

            ]

        )
        ->pluck('id');





        $studentPlan
            ->models()
            ->sync(
                $studentModels
            );



        $developerPlan
            ->models()
            ->sync(
                $developerModels
            );


    }

}
