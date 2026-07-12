<?php

namespace App\Application\Capabilities;


use App\Models\User;


class CapabilityResolver
{


    public function enabled(
        User $user,
        string $key
    ): bool {


        return (bool) $this->value(
            $user,
            $key,
            false
        );

    }

    public function value(

        User $user,

        string $key,

        mixed $default = null

    ): mixed {

        $plan = $user->currentPlan();

        if (!$plan) {

            return $default;

        }

        if ($key === 'model') {

            return $plan->model ?? $default;

        }

        return data_get(

            $plan->capabilities,

            $key,

            $default

        );

    }
}
