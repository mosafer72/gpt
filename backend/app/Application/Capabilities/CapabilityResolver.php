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


        $plan = $user->plan;


        if (!$plan) {

            return $default;

        }


        /**
         * مدل جدا از features
         */
        if ($key === 'model') {

            return $plan->model ?? $default;

        }


        return data_get(
            $plan->features,
            $key,
            $default
        );

    }

}
