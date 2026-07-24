<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceSubscription;


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
                'phone' => '09190941931'
            ],

            [
                'name' => 'محمد',

                'phone_verified_at' => now()
            ]

        );



        /*
        |--------------------------------------------------------------------------
        | Workspace Type
        |--------------------------------------------------------------------------
        */

        $type = WorkspaceType::where(
            'key',
            'student'
        )->firstOrFail();




        /*
        |--------------------------------------------------------------------------
        | Workspace Plan
        |--------------------------------------------------------------------------
        */

        $plan = WorkspacePlan::where(
            'name',
            'student-basic'
        )->firstOrFail();




        /*
        |--------------------------------------------------------------------------
        | Workspace
        |--------------------------------------------------------------------------
        */

        $workspace = Workspace::firstOrCreate(

            [

                'user_id' => $user->id,

                'workspace_type_id' => $type->id

            ],


            [

                'workspace_plan_id' => $plan->id,


                /*
                |--------------------------------------------------------------------------
                | Required field
                |--------------------------------------------------------------------------
                */

                'type' => $type->key,


                'name' => 'Student Workspace',


                'started_at' => now(),


                'expires_at' => now()
                    ->addDays(
                        $plan->duration_days
                    ),


                'is_active' => true

            ]

        );





        /*
        |--------------------------------------------------------------------------
        | Workspace Subscription
        |--------------------------------------------------------------------------
        */

        WorkspaceSubscription::updateOrCreate(

            [

                'user_id' => $user->id,


                'workspace_id' => $workspace->id,


                'workspace_plan_id' => $plan->id

            ],


            [

                'started_at' => now(),


                'expires_at' => now()
                    ->addDays(
                        $plan->duration_days
                    ),


                'is_active' => true,


                'transaction_id' => 'free'

            ]

        );



        $this->command->info(
            '✅ User workspace created'
        );

    }

}
