<?php

namespace App\Application\Services;


use App\Models\TokenWallet;
use App\Models\Workspace;



class TokenWalletService
{


    public function get(
        Workspace $workspace
    ): TokenWallet {

        return TokenWallet::firstOrCreate(

            [

                'workspace_id' => $workspace->id

            ],


            [

                'total_tokens' => 0,

                'used_tokens' => 0,

                'remaining_tokens' => 0

            ]

        );

    }





    public function initialize(
        Workspace $workspace,
        int $tokens
    ): TokenWallet {


        return TokenWallet::updateOrCreate(

            [

                'workspace_id' => $workspace->id

            ],


            [

                'total_tokens' => $tokens,

                'used_tokens' => 0,

                'remaining_tokens' => $tokens

            ]

        );

    }





    public function consume(
        Workspace $workspace,
        int $tokens
    ): TokenWallet {


        $wallet = $this->get(
            $workspace
        );


        // if (
        //     $wallet->remaining_tokens < $tokens
        // ) {

        //     throw new \RuntimeException(
        //         'Insufficient tokens'
        //     );

        // }


        // $wallet->decrement(
        //     'remaining_tokens',
        //     $tokens
        // );


        // $wallet->increment(
        //     'used_tokens',
        //     $tokens
        // );


        return $wallet->refresh();

    }





    public function hasEnough(
        Workspace $workspace,
        int $tokens
    ): bool {

        return $this->get($workspace)
            ->remaining_tokens >= $tokens;

    }
    public function upgrade(

        Workspace $workspace,

        int $tokens

    ): TokenWallet {


        $wallet = $this->get(
            $workspace
        );


        $wallet->increment(

            'total_tokens',

            $tokens

        );


        $wallet->increment(

            'remaining_tokens',

            $tokens

        );


        return $wallet->refresh();

    }
    public function check(
        Workspace $workspace,
        int $tokens
    ): void {

        if (
            !$this->hasEnough(
                $workspace,
                $tokens
            )
        ) {

            throw new \RuntimeException(
                'Insufficient tokens'
            );

        }

    }


    public function createFromPlan(
        Workspace $workspace
    ): TokenWallet {


        $subscription =

            $workspace
                ->workspaceSubscriptions()
                ->where(
                    'is_active',
                    true
                )
                ->with(
                    'workspacePlan'
                )
                ->first();





        $plan =
            $subscription?->workspacePlan;





        if (!$plan) {


            throw new \RuntimeException(
                'Workspace has no active plan'
            );


        }






        return TokenWallet::updateOrCreate(

            [

                'workspace_id' =>
                    $workspace->id

            ],


            [

                'total_tokens' =>
                    $plan->token_limit,


                'used_tokens' =>
                    0,


                'remaining_tokens' =>
                    $plan->token_limit

            ]

        );

    }

    public function resetFromPlan(
        Workspace $workspace,
        int $tokens
    ): TokenWallet {


        return TokenWallet::updateOrCreate(

            [
                'workspace_id' => $workspace->id
            ],

            [

                'total_tokens' => $tokens,

                'used_tokens' => 0,

                'remaining_tokens' => $tokens

            ]

        );

    }

}
