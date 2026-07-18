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



        /*
        |--------------------------------------------------------------------------
        | Dynamic Capabilities
        |--------------------------------------------------------------------------
        */


        return data_get(
            $plan->capabilities,
            $key,
            $default
        );

    }



    /**
     * مدل فعال کاربر
     */
    public function model(
        User $user,
        ?string $requestedModel = null
    ): ?string {


        $plan = $user->currentPlan();


        if (!$plan) {

            return null;

        }



        /*
        |--------------------------------------------------------------------------
        | مدل انتخاب شده توسط کاربر
        |--------------------------------------------------------------------------
        */


        if (
            $user->selected_model_id
        ) {


            $model =
                $plan
                    ->models()
                    ->where(
                        'ai_models.id',
                        $user->selected_model_id
                    )
                    ->first();



            if ($model) {

                return $model->model_key;

            }

        }



        /*
        |--------------------------------------------------------------------------
        | مدل پیش فرض پلن
        |--------------------------------------------------------------------------
        */


        return $plan
            ->defaultModel()
            ?->model_key;

    }

}
