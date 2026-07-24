<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Services\WorkspaceService;
use App\Application\Services\WorkspaceSubscriptionService;
use App\Application\Services\TokenWalletService;
use App\Models\Usage;
use Illuminate\Http\Request;


class DashboardController extends Controller
{


    public function __construct(

        protected WorkspaceService $workspaceService,

        protected WorkspaceSubscriptionService $subscriptionService,

        protected TokenWalletService $walletService

    ) {

    }



    public function index(Request $request)
    {

        $user = $request->user();



        /*
        |--------------------------------------------------------------------------
        | Active Workspace
        |--------------------------------------------------------------------------
        */

        $workspace = $this->workspaceService
            ->current($user);





        /*
        |--------------------------------------------------------------------------
        | Subscription
        |--------------------------------------------------------------------------
        */

        $subscription = null;


        if ($workspace) {

            $subscription = $this->subscriptionService
                ->active($workspace);

        }





        /*
        |--------------------------------------------------------------------------
        | Wallet
        |--------------------------------------------------------------------------
        */

        $wallet = null;


        if ($workspace) {

            $wallet = $this->walletService
                ->get($workspace);

        }





        /*
        |--------------------------------------------------------------------------
        | Today Usage
        |--------------------------------------------------------------------------
        */

        $usage = collect();



        if ($workspace) {

            $usage = Usage::where(

                'workspace_id',

                $workspace->id

            )
                ->whereDate(

                    'created_at',

                    today()

                )
                ->get();

        }





        return response()->json([


            'user' => $user,


            'workspace' => $workspace,


            'subscription' => $subscription,


            'wallet' => $wallet,



            'today_usage' => [


                'requests' => $usage->count(),


                'input_tokens' => $usage->sum(
                    'input_tokens'
                ),


                'output_tokens' => $usage->sum(
                    'output_tokens'
                ),


                'total_tokens' => $usage->sum(
                    'total_tokens'
                )

            ]

        ]);

    }


}
