<?php

namespace App\Application\Services;


use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceType;



class WorkspaceService
{


    public function __construct(

        protected TokenWalletService $walletService

    ) {

    }



    public function activeWorkspaces(User $user)
    {
        return $user
            ->workspaces()
            ->with([
                'workspaceType',
                'wallet',
                'workspaceSubscriptions' => function ($query) {
                    $query->where('is_active', true)
                        ->where('expires_at', '>', now())
                        ->with(['workspacePlan.models']);
                }
            ])
            ->where('is_active', true)
            ->get(); // استفاده از get به جای first
    }



    public function current(
        User $user,
        ?int $workspaceId = null
    ): ?Workspace {
        $query = $user
            ->workspaces()
            ->with([
                'workspaceType',
                'wallet',
                'workspaceSubscriptions' => function ($query) {
                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'expires_at',
                            '>',
                            now()
                        )
                        ->with([
                            'workspacePlan.models'
                        ]);
                }
            ])
            ->where(
                'is_active',
                true
            );

        // اگر آیدی ورک‌اسپیس ارسال شده بود، بر اساس آن فیلتر کن
        if ($workspaceId) {
            $query->where('workspaces.id', $workspaceId);
        }

        return $query->first();
    }






    public function create(

        User $user,

        WorkspaceType $type,

        WorkspacePlan $plan

    ): Workspace {



        $workspace =

            Workspace::firstOrCreate(

                [

                    'user_id' =>
                        $user->id,


                    'workspace_type_id' =>
                        $type->id,

                ],


                [

                    'name' =>
                        $type->display_name,


                    'type' =>
                        $type->key,


                    'started_at' =>
                        now(),


                    'is_active' =>
                        true,

                ]

            );







        $subscription =

            $workspace
                ->workspaceSubscriptions()
                ->where(
                    'is_active',
                    true
                )
                ->first();







        if (!$subscription) {


            $workspace
                ->workspaceSubscriptions()
                ->create([


                    'user_id' =>
                        $user->id,


                    'workspace_plan_id' =>
                        $plan->id,


                    'started_at' =>
                        now(),


                    'expires_at' =>
                        now()->addDays(
                            $plan->duration_days
                        ),


                    'is_active' =>
                        true,


                    'transaction_id' =>
                        'free'


                ]);


        }







        $workspace->load(
            'workspaceSubscriptions.workspacePlan.models'
        );







        if (!$workspace->wallet) {


            $this->walletService
                ->createFromPlan(
                    $workspace
                );


        }







        return $workspace->refresh();


    }









    public function createByType(

        User $user,

        string $typeKey

    ): ?Workspace {


        $type =

            WorkspaceType::where(
                'key',
                $typeKey
            )
                ->first();





        if (!$type) {

            return null;

        }






        $plan =

            WorkspacePlan::where(
                'workspace_type_id',
                $type->id
            )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'price'
                )
                ->first();






        if (!$plan) {

            return null;

        }






        return $this->create(

            user: $user,

            type: $type,

            plan: $plan

        );


    }









    public function changePlan(

        Workspace $workspace,

        WorkspacePlan $plan

    ): Workspace {



        $workspace
            ->workspaceSubscriptions()
            ->where(
                'is_active',
                true
            )
            ->update([

                'is_active' => false

            ]);








        $workspace
            ->workspaceSubscriptions()
            ->create([


                'user_id' =>
                    $workspace->user_id,


                'workspace_plan_id' =>
                    $plan->id,


                'started_at' =>
                    now(),


                'expires_at' =>
                    now()->addDays(
                        $plan->duration_days
                    ),


                'is_active' =>
                    true,


                'transaction_id' =>
                    'plan_change'


            ]);







        return $workspace->refresh();


    }









    public function hasAccess(
        Workspace $workspace
    ): bool {


        return

            $workspace->is_active

            &&

            $workspace
                ->workspaceSubscriptions()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'expires_at',
                    '>',
                    now()
                )
                ->exists();


    }









    public function plan(
        Workspace $workspace
    ): ?WorkspacePlan {


        return

            $workspace
                ->workspaceSubscriptions()
                ->where(
                    'is_active',
                    true
                )
                ->with(
                    'workspacePlan'
                )
                ->first()
                    ?->workspacePlan;


    }









    public function models(
        Workspace $workspace
    ) {


        return

            $this->plan(
                $workspace
            )
                    ?->models

            ??

            collect();


    }









    public function all(
        User $user
    ) {


        return $user
            ->workspaces()
            ->with([

                'workspaceType',

                'workspaceSubscriptions.workspacePlan'

            ])
            ->get();


    }


}
