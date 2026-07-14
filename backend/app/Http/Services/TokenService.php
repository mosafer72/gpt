<?php

namespace App\Services;

use App\Models\User;

class TokenService
{

    public function check(User $user, int $estimatedTokens = 0): bool
    {

        $wallet = $user->tokenWallet;


        if (!$wallet) {
            return false;
        }


        return $wallet->remaining_tokens >= $estimatedTokens;

    }



    public function consume(
        User $user,
        int $tokens
    ): void {


        $wallet = $user->tokenWallet;


        if (!$wallet) {
            return;
        }


        $wallet->increment(
            'used_tokens',
            $tokens
        );


        $wallet->decrement(
            'remaining_tokens',
            $tokens
        );

    }

}
