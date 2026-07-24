<?php

namespace App\Application\Services;


use App\Models\User;
use App\Models\Workspace;
use App\Models\AiModel;
use RuntimeException;



class WorkspaceAccessService
{


    public function __construct(

        protected WorkspaceService $workspaceService,

        protected WorkspaceSubscriptionService $subscriptionService,

        protected TokenWalletService $walletService

    ) {

    }





    /**
     * بررسی کامل دسترسی Chat
     */
    public function check(
        User $user,
        ?string $modelKey = null,
        int $estimatedTokens = 0
    ): Workspace {


        /*
        |--------------------------------------------------------------------------
        | Workspace
        |--------------------------------------------------------------------------
        */

        $workspace =
            $this->workspaceService
                ->current($user);



        if (!$workspace) {

            throw new RuntimeException(
                'No active workspace'
            );

        }






        /*
        |--------------------------------------------------------------------------
        | Subscription
        |--------------------------------------------------------------------------
        */

        $subscription =
            $this->subscriptionService
                ->active($workspace);



        if (!$subscription) {

            throw new RuntimeException(
                'No active subscription'
            );

        }





        if (
            !$subscription->expires_at
            ||
            $subscription->expires_at->isPast()
        ) {

            throw new RuntimeException(
                'Workspace subscription expired'
            );

        }





        /*
        |--------------------------------------------------------------------------
        | Model Permission
        |--------------------------------------------------------------------------
        */

        if ($modelKey) {


            $allowed =
                $subscription
                    ->workspacePlan
                    ->models()
                    ->where(
                        'key',
                        $modelKey
                    )
                    ->exists();



            if (!$allowed) {

                throw new RuntimeException(
                    'Model is not available for this plan'
                );

            }

        }







        /*
        |--------------------------------------------------------------------------
        | Token Balance
        |--------------------------------------------------------------------------
        */


        if ($estimatedTokens > 0) {


            if (
                !$this->walletService
                    ->hasEnough(
                        $workspace,
                        $estimatedTokens
                    )
            ) {

                throw new RuntimeException(
                    'Insufficient tokens'
                );

            }


        }


        return $workspace;

    }

    public function resolveModel(
        Workspace $workspace,
        ?string $requested = null
    ): AiModel {


        $subscription =
            $this->subscriptionService
                ->active($workspace);



        if (!$subscription) {

            throw new RuntimeException(
                'No active subscription'
            );

        }



        $query =
            $subscription
                ->workspacePlan
                ->models()
                ->where(
                    'is_active',
                    true
                );



        if ($requested) {


            $model =
                $query
                    ->where(
                        'key',
                        $requested
                    )
                    ->first();



            if (!$model) {

                throw new RuntimeException(
                    'Model not allowed'
                );

            }


            return $model;

        }



        return $query->firstOrFail();

    }

}
