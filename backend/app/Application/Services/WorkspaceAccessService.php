<?php

namespace App\Application\Services;


use App\Models\User;
use App\Models\Workspace;
use App\Models\AiModel;
use App\Models\WorkspacePlan;

use RuntimeException;



class WorkspaceAccessService
{


    public function __construct(

        protected WorkspaceService $workspaceService,

        protected WorkspaceSubscriptionService $subscriptionService,

        protected TokenWalletService $walletService

    ) {

    }







    public function check(

        User $user,

        ?string $modelKey = null,

        int $estimatedTokens = 0

    ): Workspace {


        $workspace =
            $this->workspaceService
                ->current($user);



        if (!$workspace) {

            throw new RuntimeException(
                'No active workspace'
            );

        }




        $plan =
            $this->resolvePlan(
                $workspace
            );




        if ($modelKey) {


            $allowed =
                $plan
                    ->models()
                    ->where(
                        'key',
                        $modelKey
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->exists();



            if (!$allowed) {

                throw new RuntimeException(
                    'Model is not available for this plan'
                );

            }


        }





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









    public function resolvePlan(
        Workspace $workspace
    ): WorkspacePlan {



        $subscription =
            $this->subscriptionService
                ->active(
                    $workspace
                );



        if (!$subscription) {

            throw new RuntimeException(
                'No active subscription'
            );

        }




        if (
            $subscription->expires_at
            &&
            $subscription->expires_at->isPast()
        ) {


            throw new RuntimeException(
                'Workspace subscription expired'
            );


        }




        return $subscription->workspacePlan;


    }









    public function resolveModel(

        Workspace $workspace,

        ?string $requested = null

    ): AiModel {



        $plan =
            $this->resolvePlan(
                $workspace
            );




        $models =
            $plan
                ->models()
                ->where(
                    'is_active',
                    true
                );





        if ($requested) {


            $model =
                (clone $models)
                    ->where(
                        'key',
                        $requested
                    )
                    ->first();



            if (!$model) {

                throw new RuntimeException(
                    'Requested model is not allowed'
                );

            }



            return $model;


        }






        $model =
            (clone $models)
                ->where(
                    'is_default',
                    true
                )
                ->first();




        if ($model) {

            return $model;

        }





        $model =
            $models->first();




        if (!$model) {

            throw new RuntimeException(
                'No model assigned to plan'
            );

        }





        return $model;


    }









    public function resolveCapabilities(
        Workspace $workspace
    ): array {


        $plan =
            $this->resolvePlan(
                $workspace
            );



        $typeCapabilities =

            data_get(

                $workspace->type,

                'capabilities',

                []

            );





        $planCapabilities =

            $plan->capabilities
            ??
            [];





        return array_merge(

            [

                'planner' => false,

                'memory' => false,

                'documents' => false,

                'image' => false,

                'max_tokens' => 1000

            ],

            $typeCapabilities,

            $planCapabilities

        );


    }









    public function canUseModel(

        Workspace $workspace,

        string $modelKey

    ): bool {



        $plan =
            $this->resolvePlan(
                $workspace
            );




        return $plan
            ->models()
            ->where(
                'key',
                $modelKey
            )
            ->where(
                'is_active',
                true
            )
            ->exists();


    }


}
