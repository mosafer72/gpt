<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Services\TokenWalletService;
use App\Models\WorkspaceSubscription;
use Illuminate\Http\Request;


class TokenWalletController extends Controller
{

    public function __construct(
        protected TokenWalletService $walletService
    )
    {

    }



    public function show(Request $request)
    {

        $user = $request->user();


        $subscriptions = WorkspaceSubscription::with([

            'workspace.workspaceType',

            'workspace.plan',

            'workspace.wallet',

            'workspacePlan'

        ])
        ->where(
            'user_id',
            $user->id
        )
        ->where(
            'is_active',
            true
        )
        ->get();



        $wallets=[];



        foreach($subscriptions as $subscription)
        {


            $workspace=$subscription->workspace;


            if(!$workspace){
                continue;
            }



            $wallet=$this->walletService
                ->get($workspace);



            $wallets[]=[


                'workspace'=>[

                    'id'=>$workspace->id,

                    'name'=>$workspace
                        ->workspaceType
                        ->name,

                ],


                'plan'=>$subscription
                    ->workspacePlan
                    ->display_name,


                'total_tokens'=>$wallet->total_tokens,


                'used_tokens'=>$wallet->used_tokens,


                'remaining_tokens'=>$wallet->remaining_tokens


            ];

        }



        return response()->json([

            'workspaces'=>$wallets

        ]);

    }

}
