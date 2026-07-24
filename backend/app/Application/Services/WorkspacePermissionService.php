<?php

namespace App\Application\Services;


use App\Models\Workspace;


class WorkspacePermissionService
{

    public function __construct(
        protected WorkspaceSubscriptionService $subscriptionService
    ) {

    }



    public function can(
        Workspace $workspace,
        string $permission
    ): bool {


        /*
        |--------------------------------------------------------------------------
        | Workspace Override
        |--------------------------------------------------------------------------
        */

        $override =
            $workspace
                ->permissions()
                ->where(
                    'key',
                    $permission
                )
                ->first();


        if ($override) {
            return $override
                ->pivot
                ->is_enabled;
        }




        /*
        |--------------------------------------------------------------------------
        | Plan Permission
        |--------------------------------------------------------------------------
        */


        $subscription =
            $this->subscriptionService
                ->active($workspace);



        if (!$subscription) {
            return false;
        }



        return $subscription
            ->workspacePlan
            ->permissions()
            ->where(
                'key',
                $permission
            )
            ->exists();


    }


}
