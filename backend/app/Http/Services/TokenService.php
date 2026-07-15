<?php

namespace App\Http\Services;


use App\Models\User;
use App\Models\TokenWallet;


class TokenService
{


    public function check(
        User $user,
        int $estimatedTokens = 0
    ): bool {


        $wallet = $this->getWallet($user);



        return $wallet->remaining_tokens >= $estimatedTokens;

    }





    public function consume(
        User $user,
        int $tokens
    ): void {


        $wallet = $this->getWallet($user);



        $wallet->increment(

            'used_tokens',

            $tokens

        );



        $wallet->decrement(

            'remaining_tokens',

            $tokens

        );


    }







    protected function getWallet(
        User $user
    ): TokenWallet {


        if ($user->tokenWallet) {

            return $user->tokenWallet;

        }




        /*
        |--------------------------------------------------------------------------
        | ساخت کیف پول از روی پلن کاربر
        |--------------------------------------------------------------------------
        */


        $plan = $user->plan;



        if (!$plan) {

            throw new \Exception(
                'کاربر پلن فعال ندارد'
            );

        }




        return TokenWallet::create([

            'user_id'=>$user->id,

            'plan_id'=>$plan->id,

            'total_tokens'=>$plan->token_limit,

            'used_tokens'=>0,

            'remaining_tokens'=>$plan->token_limit,

        ]);

    }


}
