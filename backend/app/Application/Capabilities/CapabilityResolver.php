<?php

namespace App\Application\Capabilities;

use App\Models\User;


class CapabilityResolver
{


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







    public function value(
        User $user,
        string $key,
        mixed $default = null
    ): mixed {


        $plan =
            $user->currentPlan();



        if(!$plan){

            return $default;

        }



        return data_get(

            $plan->capabilities,

            $key,

            $default

        );

    }







    /**
     * Resolve available AI model
     */
    public function model(
        User $user,
        ?string $requestedModel = null
    ): ?string {


        $plan =
            $user->currentPlan();



        if(!$plan){

            return null;

        }



        /*
        |--------------------------------------------------------------------------
        | 1. Requested model from user
        |--------------------------------------------------------------------------
        */


        if($requestedModel){


            $model =
                $plan
                ->models()
                ->where(
                    'model_key',
                    $requestedModel
                )
                ->first();



            if($model){

                return $model->model_key;

            }

        }







        /*
        |--------------------------------------------------------------------------
        | 2. User selected model
        |--------------------------------------------------------------------------
        */


        if(
            $user->selected_model_id
        ){


            $model =
                $plan
                ->models()
                ->where(
                    'ai_models.id',
                    $user->selected_model_id
                )
                ->first();



            if($model){

                return $model->model_key;

            }

        }








        /*
        |--------------------------------------------------------------------------
        | 3. Plan default model
        |--------------------------------------------------------------------------
        */


        $default =
            $plan->defaultModel();



        if($default){

            return $default->model_key;

        }





        return null;

    }


}
