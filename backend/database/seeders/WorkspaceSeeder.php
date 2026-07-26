<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;

use App\Application\Services\WorkspaceService;



class WorkspaceSeeder extends Seeder
{


    public function run(): void
    {


        $user = User::first();



        if(!$user){

            return;

        }





        $service =
            app(
                WorkspaceService::class
            );






        /*
        |--------------------------------------------------------------------------
        | Personal Workspace
        |--------------------------------------------------------------------------
        */


        $personalType =
            WorkspaceType::where(
                'key',
                'personal'
            )->first();



        $personalPlan =
            WorkspacePlan::where(
                'name',
                'personal-basic'
            )->first();




        if($personalType && $personalPlan)
        {


            $service->create(

                $user,

                $personalType,

                $personalPlan

            );


        }







        /*
        |--------------------------------------------------------------------------
        | Student Workspace
        |--------------------------------------------------------------------------
        */


        $studentType =
            WorkspaceType::where(
                'key',
                'student'
            )->first();




        $studentPlan =
            WorkspacePlan::where(
                'name',
                'student-basic'
            )->first();




        if($studentType && $studentPlan)
        {


            $service->create(

                $user,

                $studentType,

                $studentPlan

            );


        }


    }

}
