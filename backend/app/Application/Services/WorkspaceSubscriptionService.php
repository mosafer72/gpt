<?php

namespace App\Application\Services;


use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceSubscription;
use App\Application\Services\TokenWalletService;



class WorkspaceSubscriptionService
{

    public function __construct(

        protected TokenWalletService $walletService

    ) {
    }

    public function active(
        Workspace $workspace
    ): ?WorkspaceSubscription {

        return $workspace
            ->workspaceSubscriptions()
            ->where('is_active', true)
            ->where(
                'expires_at',
                '>',
                now()
            )
            ->with([
                'workspacePlan.models'
            ])
            ->first();

    }




    public function subscribe(
        User $user,
        Workspace $workspace,
        WorkspacePlan $plan,
        string $transaction = 'free'
    ) {


        $current = $this->active(
            $workspace
        );


        if ($current) {

            return $current;

        }



        return WorkspaceSubscription::create([

            'user_id' => $user->id,

            'workspace_id' => $workspace->id,

            'workspace_plan_id' => $plan->id,

            'started_at' => now(),

            'expires_at' => now()
                ->addDays(
                    $plan->duration_days
                ),

            'is_active' => true,

            'transaction_id' => $transaction

        ]);

    }

    public function upgrade(

        User $user,

        Workspace $workspace,

        WorkspacePlan $plan,

        string $transaction = 'upgrade'

    ): WorkspaceSubscription {


        $this->cancelCurrent(
            $workspace
        );



        $subscription = WorkspaceSubscription::create([


            'user_id' => $user->id,

            'workspace_id' => $workspace->id,

            'workspace_plan_id' => $plan->id,

            'started_at' => now(),

            'expires_at' => now()
                ->addDays(
                    $plan->duration_days
                ),

            'is_active' => true,

            'transaction_id' => $transaction


        ]);



        /*
        |--------------------------------------------------------------------------
        | Sync Workspace Plan
        |--------------------------------------------------------------------------
        */

        $workspace->update([

            'workspace_plan_id' => $plan->id,

            'expires_at' => $subscription->expires_at

        ]);


        $workspace->refresh();


        /*
        |--------------------------------------------------------------------------
        | Add Token Balance
        |--------------------------------------------------------------------------
        */

        $this->walletService
            ->resetFromPlan(

                $workspace,

                $plan->token_limit

            );



        return $subscription;

    }


    public function cancelCurrent(
        Workspace $workspace
    ): void {


        $workspace
            ->workspaceSubscriptions()
            ->where('is_active', true)
            ->update([

                'is_active' => false

            ]);

    }




}
