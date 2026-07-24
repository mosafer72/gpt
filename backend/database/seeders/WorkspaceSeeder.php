<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workspace;
use App\Models\User;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;
use Carbon\Carbon;


class WorkspaceSeeder extends Seeder
{

    public function run(): void
    {

        $user = User::first();


        if (!$user) {
            return;
        }



        $workspaceType = WorkspaceType::where(
            'key',
            'personal'
        )->first();



        $plan = WorkspacePlan::where(
            'name',
            'personal_free'
        )->first();



        if (!$workspaceType || !$plan) {
            return;
        }



        $startedAt = Carbon::now();


        Workspace::updateOrCreate(

            [
                'user_id' => $user->id,

                'workspace_type_id' => $workspaceType->id,
            ],


            [

                'workspace_plan_id' => $plan->id,


                /*
                 | Required column in DB
                 */
                'type' => 'personal',


                'name' => 'Student Workspace',


                'started_at' => $startedAt,


                'expires_at' => $startedAt->copy()
                    ->addDays($plan->duration_days),


                'is_active' => true,


            ]

        );

    }

}
