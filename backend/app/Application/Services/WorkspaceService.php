<?php

namespace App\Application\Services;


use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceType;
use App\Application\Services\TokenWalletService;



class WorkspaceService
{

    public function __construct(

        protected TokenWalletService $walletService

    ) {
    }
    /**
     * Workspace فعال کاربر
     */
    public function current(
        User $user
    ): ?Workspace {


        return $user
            ->workspaces()
            ->with([
                'workspaceType',
                'wallet',
                'workspaceSubscriptions' => function ($query) {

                    $query
                        ->where('is_active', true)
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
            )
            ->first();

    }




    /**
     * ساخت Workspace جدید
     */
    public function create(
        User $user,
        WorkspaceType $type,
        WorkspacePlan $plan
    ): Workspace {


        $existing = Workspace::where(

            'user_id',

            $user->id

        )
            ->where(

                'workspace_type_id',

                $type->id

            )
            ->first();



        if ($existing) {

            return $existing;

        }



        $workspace = Workspace::create([

            'user_id' => $user->id,

            'workspace_type_id' => $type->id,

            'workspace_plan_id' => $plan->id,

            'name' => $plan->display_name,

            'started_at' => now(),

            'expires_at' => now()
                ->addDays(
                    $plan->duration_days
                ),

            'is_active' => true

        ]);


        $this->walletService
            ->createFromPlan(
                $workspace
            );


        return $workspace;

    }





    /**
     * تغییر پلن Workspace
     */
    public function changePlan(
        Workspace $workspace,
        WorkspacePlan $plan
    ): Workspace {


        $workspace->update([

            'workspace_plan_id' => $plan->id,


            'expires_at' => now()
                ->addDays(
                    $plan->duration_days
                )

        ]);


        return $workspace->refresh();

    }





    /**
     * بررسی فعال بودن Workspace
     */
    public function hasAccess(
        Workspace $workspace
    ): bool {


        return
            $workspace->is_active
            &&
            $workspace->expires_at
                ->isFuture();

    }





    /**
     * گرفتن مدل‌های مجاز Workspace
     */
    public function models(
        Workspace $workspace
    ) {

        $subscription =
            $workspace
                ->workspaceSubscriptions
                ->first();


        if (!$subscription) {

            return collect();

        }


        return $subscription
            ->workspacePlan
            ->models;

    }





    /**
     * گرفتن Plan فعلی
     */
    public function plan(
        Workspace $workspace
    ): ?WorkspacePlan {

        return $workspace
            ->workspaceSubscriptions
            ->first()
                ?->workspacePlan;

    }



}
