<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use App\Models\PaymentTransaction;
use App\Models\TokenWallet;
use App\Http\Services\ZarinpalService;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;



class SubscriptionController extends Controller
{


    public function __construct(
        protected ZarinpalService $zarinpal
    ) {
    }



    /**
     * لیست پلن ها
     */
    public function plans()
    {

        $plans =
            Plan::with('models')
                ->where(
                    'is_active',
                    true
                )
                ->get();


        return response()->json($plans);

    }




    /**
     * اشتراک فعال Workspace
     */
    public function mySubscription(): JsonResponse
    {

        $user = Auth::user();


        $workspace =
            Workspace::where(
                'user_id',
                $user->id
            )
                ->firstOrFail();



        $subscription =
            $workspace
                ->activeSubscription()
                ->with([
                    'plan.models'
                ])
                ->first();



        if (!$subscription) {

            return response()->json([

                'has_subscription' => false

            ]);

        }



        return response()->json([

            'has_subscription' => true,


            'subscription' => [

                'id' =>
                    $subscription->id,


                'plan' =>
                    $subscription->plan,


                'started_at' =>
                    $subscription->started_at,


                'expires_at' =>
                    $subscription->expires_at,


                'days_remaining' =>
                    $subscription->days_remaining,


                'is_valid' =>
                    $subscription->isValid()

            ]

        ]);

    }





    /**
     * شروع پرداخت
     */
    public function initiatePayment(
        Request $request
    ): JsonResponse {


        $user = Auth::user();



        $request->validate([

            'workspace_id'
            =>
                'required|exists:workspaces,id',


            'plan_id'
            =>
                'required|exists:plans,id'

        ]);




        $workspace =
            Workspace::where(
                'id',
                $request->workspace_id
            )
                ->where(
                    'user_id',
                    $user->id
                )
                ->firstOrFail();




        $plan =
            Plan::findOrFail(
                $request->plan_id
            );




        $amount =
            $plan->price;



        /**
         * پلن رایگان
         */
        if ($amount <= 0) {

            return $this->activateFreePlan(
                $workspace,
                $plan
            );

        }





        $callback =
            route(
                'payment.callback'
            );





        $result =
            $this->zarinpal
                ->requestPayment(

                    $amount,

                    $callback,

                    'اشتراک ' . $plan->name,

                    $user->phone

                );





        if (!$result['success']) {

            return response()->json([

                'error' =>
                    $result['message']

            ], 500);

        }






        $transaction =
            PaymentTransaction::create([

                'user_id'
                =>
                    $user->id,


                'workspace_id'
                =>
                    $workspace->id,


                'workspace_plan_id'
                =>
                    $plan->id,


                'amount'
                =>
                    $amount,


                'authority'
                =>
                    $result['authority'],


                'status'
                =>
                    'pending'


            ]);






        return response()->json([


            'payment_url'
            =>
                $result['payment_url'],


            'authority'
            =>
                $transaction->authority


        ]);

    }





    /**
     * فعال کردن پلن رایگان
     */
    private function activateFreePlan(
        Workspace $workspace,
        Plan $plan
    ) {


        WorkspaceSubscription::where(
            'workspace_id',
            $workspace->id
        )
            ->update([

                'is_active' => false

            ]);




        $subscription =
            WorkspaceSubscription::create([


                'workspace_id'
                =>
                    $workspace->id,


                'workspace_plan_id'
                =>
                    $plan->id,
                'user_id'
                =>
                    auth()->user()->id,

                'started_at'
                =>
                    now(),


                'expires_at'
                =>
                    now()->addDays(
                        $plan->duration_days
                    ),


                'is_active'
                =>
                    true


            ]);





        $this->createWallet(
            $workspace,
            $plan
        );





        return response()->json([

            'success' => true,


            'subscription' => $subscription

        ]);

    }







    /**
     * Callback زرین پال
     */
    public function paymentCallback(
        Request $request
    ) {


        $authority =
            $request->query(
                'Authority'
            );



        $status =
            $request->query(
                'Status'
            );




        if ($status !== 'OK') {

            return redirect(
                '/payment/failed'
            );

        }





        $transaction =
            PaymentTransaction::where(
                'authority',
                $authority
            )
                ->firstOrFail();





        if (
            $transaction->status !== 'pending'
        ) {

            return redirect(
                '/payment/failed'
            );

        }





        $verify =
            $this->zarinpal
                ->verifyPayment(

                    $authority,

                    $transaction->amount

                );





        if (!$verify['success']) {


            $transaction->update([

                'status' => 'failed'

            ]);



            \Log::error(
                'Payment failed',
                [
                    'authority' => $authority
                ]
            );



            return redirect(
                '/payment/failed'
            );

        }






        $transaction->update([

            'status' => 'paid',


            'ref_id'
            =>
                $verify['ref_id']

        ]);






        WorkspaceSubscription::where(

            'workspace_id',

            $transaction->workspace_id

        )
            ->update([

                'is_active' => false

            ]);







        $subscription =
            WorkspaceSubscription::create([


                'workspace_id'
                =>
                    $transaction->workspace_id,


                'plan_id'
                =>
                    $transaction->plan_id,

                'user_id'
                =>
                    auth()->user()->id,


                'started_at'
                =>
                    now(),


                'expires_at'
                =>
                    now()->addDays(
                        $transaction->plan->duration_days
                    ),


                'is_active'
                =>
                    true,


                'transaction_id'
                =>
                    $verify['ref_id']


            ]);






        $this->createWallet(

            $transaction->workspace,

            $transaction->plan

        );






        return redirect(

            '/payment/success?ref_id='
            . $verify['ref_id']

        );

    }







    /**
     * ساخت کیف پول Workspace
     */
    private function createWallet(
        Workspace $workspace,
        Plan $plan
    ) {


        $wallet =
            TokenWallet::firstOrCreate([

                'workspace_id'
                =>
                    $workspace->id

            ], [

                'total_tokens' => 0,

                'used_tokens' => 0,

                'remaining_tokens' => 0

            ]);





        $wallet->update([

            'total_tokens'
            =>
                $plan->token_limit,


            'remaining_tokens'
            =>
                $plan->token_limit,


            'used_tokens'
            =>
                0

        ]);

    }






    /**
     * نمایش کیف پول
     */
    public function wallet()
    {

        $user =
            Auth::user();



        $workspace =
            Workspace::where(
                'user_id',
                $user->id
            )
                ->firstOrFail();




        $wallet =
            TokenWallet::firstOrCreate([

                'workspace_id'
                =>
                    $workspace->id

            ], [

                'total_tokens' => 0,

                'used_tokens' => 0,

                'remaining_tokens' => 0

            ]);




        return response()->json([


            'total_tokens'
            =>
                $wallet->total_tokens,


            'used_tokens'
            =>
                $wallet->used_tokens,


            'remaining_tokens'
            =>
                $wallet->remaining_tokens


        ]);

    }


}
