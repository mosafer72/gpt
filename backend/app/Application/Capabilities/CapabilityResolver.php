<?php

namespace App\Application\Capabilities;


use App\Models\User;
use App\Models\AIModel;


class CapabilityResolver
{


    public function enabled(
        User $user,
        string $key
    ): bool
    {

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

    ): mixed
    {


        $plan = $user->currentPlan();



        if (!$plan) {

            return $default;

        }




        /*
        |--------------------------------------------------------------------------
        | مدل انتخابی
        |--------------------------------------------------------------------------
        */


        if ($key === 'model') {


            /*
             * اگر کاربر مدل انتخاب کرده باشد
             * مثلا از فرانت:
             *
             * selected_model_id
             *
             */


            if ($user->selected_model_id) {


                $model = $plan
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
             * اگر کاربر انتخاب نکرده بود
             * مدل پیش فرض پلن
             */


            $defaultModel =
                $plan->defaultModel();



            if ($defaultModel) {


                return $defaultModel->model_key;


            }



            return $default;

        }






        /*
        |--------------------------------------------------------------------------
        | بقیه capability ها
        |--------------------------------------------------------------------------
        */


        return data_get(

            $plan->capabilities,

            $key,

            $default

        );


    }


}
