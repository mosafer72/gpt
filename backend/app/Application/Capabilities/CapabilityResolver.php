<?php

namespace App\Application\Capabilities;

use App\Models\User;
use App\Application\Services\WorkspaceService;
use App\Application\Services\WorkspaceAccessService;



class CapabilityResolver
{


    public function __construct(

        protected WorkspaceService $workspaceService,

        protected WorkspaceAccessService $workspaceAccessService

    ) {

    }








    /**
     * Check capability enabled
     */
    public function enabled(

        User $user,

        string $key

    ): bool {


        return (bool)

            $this->value(

                $user,

                $key,

                false

            );


    }









    /**
     * Get capability value
     */
    public function value(

        User $user,

        string $key,

        mixed $default = null

    ): mixed {


        $capabilities = $this->all(
            $user
        );


        return data_get(

            $capabilities,

            $key,

            $default

        );


    }









    /**
     * Get all capabilities
     */
    public function all(

        User $user

    ): array {


        $workspace =

            $this->workspaceService
                ->current(
                    $user
                );





        if(!$workspace){

            return [];

        }






        return $this->workspaceAccessService
            ->resolveCapabilities(

                $workspace

            ) ?? [];

    }









    /**
     * Resolve current workspace model
     */
    public function model(

        User $user,

        ?string $requestedModel = null

    ): ?string {


        $workspace =

            $this->workspaceService
                ->current(
                    $user
                );





        if(!$workspace){

            return null;

        }







        $model =

            $this->workspaceAccessService
                ->resolveModel(

                    workspace:$workspace,

                    requested:$requestedModel

                );







        return $model?->key;


    }








    /**
     * Get current workspace
     */
    public function workspace(
        User $user
    ) {


        return $this->workspaceService
            ->current(
                $user
            );

    }


}
