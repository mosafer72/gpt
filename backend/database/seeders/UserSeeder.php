<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;
use App\Application\Services\WorkspaceService;


class UserSeeder extends Seeder
{


    public function run(): void
    {


        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */


        $user = User::updateOrCreate(

            [
                'phone'=>'09190941931'
            ],


            [
                'name'=>'محمد',

                'phone_verified_at'=>now()

            ]

        );







        /*
        |--------------------------------------------------------------------------
        | Workspace Type
        |--------------------------------------------------------------------------
        */


        $type =
            WorkspaceType::where(
                'key',
                'student'
            )
            ->firstOrFail();







        /*
        |--------------------------------------------------------------------------
        | Workspace Plan
        |--------------------------------------------------------------------------
        */


        $plan =
            WorkspacePlan::where(
                'name',
                'student-basic'
            )
            ->firstOrFail();







        /*
        |--------------------------------------------------------------------------
        | Create Workspace
        |--------------------------------------------------------------------------
        */


        $service =
            app(
                WorkspaceService::class
            );




        $workspace =
            $service->create(

                user:$user,

                type:$type,

                plan:$plan

            );








        $this->command->info(

            '✅ User and workspace created'

        );


    }

}
