<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceType;


class WorkspacePlanSeeder extends Seeder
{


    public function run(): void
    {


        $personal =
            WorkspaceType::where(
                'key',
                'personal'
            )->first();



        $student =
            WorkspaceType::where(
                'key',
                'student'
            )->first();




        if(!$personal || !$student){

            return;

        }





        $plans = [



            /*
            |--------------------------------------------------------------------------
            | Personal Plans
            |--------------------------------------------------------------------------
            */


            [

                'type'=>$personal,

                'name'=>'personal-basic',

                'display_name'=>'Personal Basic',

                'price'=>0,

                'tokens'=>50000,

                'planner'=>false,

                'documents'=>false,

            ],



            [

                'type'=>$personal,

                'name'=>'personal-pro',

                'display_name'=>'Personal Pro',

                'price'=>199000,

                'tokens'=>500000,

                'planner'=>true,

                'documents'=>true,

            ],




            [

                'type'=>$personal,

                'name'=>'personal-advanced',

                'display_name'=>'Personal Advanced',

                'price'=>499000,

                'tokens'=>2000000,

                'planner'=>true,

                'documents'=>true,

            ],






            /*
            |--------------------------------------------------------------------------
            | Student Plans
            |--------------------------------------------------------------------------
            */


            [

                'type'=>$student,

                'name'=>'student-basic',

                'display_name'=>'Student Basic',

                'price'=>0,

                'tokens'=>100000,

                'planner'=>false,

                'documents'=>true,

            ],





            [

                'type'=>$student,

                'name'=>'student-pro',

                'display_name'=>'Student Pro',

                'price'=>199000,

                'tokens'=>1000000,

                'planner'=>true,

                'documents'=>true,

            ],





            [

                'type'=>$student,

                'name'=>'student-advanced',

                'display_name'=>'Student Advanced',

                'price'=>499000,

                'tokens'=>3000000,

                'planner'=>true,

                'documents'=>true,

            ],


        ];








        foreach($plans as $plan)
        {


            WorkspacePlan::updateOrCreate(

                [

                    'name'=>$plan['name']

                ],

                [

                    'workspace_type_id'=>
                        $plan['type']->id,


                    'display_name'=>
                        $plan['display_name'],


                    'price'=>
                        $plan['price'],


                    'duration_days'=>30,


                    'token_limit'=>
                        $plan['tokens'],



                    'features'=>[

                    ],



                    'capabilities'=>[

                        'memory'=>true,

                        'planner'=>
                            $plan['planner'],

                        'documents'=>
                            $plan['documents'],

                        'file_upload'=>
                            $plan['documents'],

                        'max_tokens'=>2000

                    ],



                    'is_active'=>true


                ]

            );


        }


    }

}
