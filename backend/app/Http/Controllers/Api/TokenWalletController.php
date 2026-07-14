<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TokenWallet;
use Illuminate\Http\Request;


class TokenWalletController extends Controller
{

    /**
     * دریافت وضعیت توکن کاربر
     */
    public function show(Request $request)
    {

        $user = $request->user();


        if (!$user) {

            return response()->json([
                'message'=>'Unauthorized'
            ],401);

        }



        /*
        |--------------------------------------------------------------------------
        | دریافت کیف پول توکن
        |--------------------------------------------------------------------------
        */

        $wallet = TokenWallet::firstOrCreate(

            [
                'user_id'=>$user->id
            ],

            [
                'total_tokens'=>0,
                'used_tokens'=>0,
                'remaining_tokens'=>0
            ]

        );



        return response()->json([

            'total_tokens'=>
                $wallet->total_tokens,


            'used_tokens'=>
                $wallet->used_tokens,


            'remaining_tokens'=>
                $wallet->remaining_tokens


        ]);

    }

}
