<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;

use App\Models\WorkspacePlan;
use App\Models\PaymentTransaction;

use App\Application\Services\WorkspaceService;
use App\Application\Services\WorkspaceSubscriptionService;
use App\Application\Services\TokenWalletService;

use App\Http\Services\ZarinpalService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;



class WorkspaceSubscriptionController extends Controller
{


    public function __construct(

        protected WorkspaceService $workspaceService,

        protected WorkspaceSubscriptionService $subscriptionService,

        protected TokenWalletService $walletService,

        protected ZarinpalService $zarinpal

    )
    {

    }




    /**
     * لیست پلن های Workspace
     */
    public function plans()
    {

        return response()->json([

            'success'=>true,


            'data'=>

                WorkspacePlan::with([

                    'workspaceType',

                    'models'

                ])

                ->where(
                    'is_active',
                    true
                )

                ->get()

        ]);

    }





    /**
     * Workspace فعلی کاربر
     */
    public function myWorkspace(
        Request $request
    )
    {

        $workspace =
            $this->workspaceService
                ->current(
                    $request->user()
                );



        if(!$workspace)
        {

            return response()->json([

                'success'=>false,

                'message'=>'Workspace فعال وجود ندارد'

            ]);

        }




        $subscription =
            $this->subscriptionService
                ->active(
                    $workspace
                );




        return response()->json([

            'success'=>true,


            'data'=>[

                'workspace'=>$workspace,

                'subscription'=>$subscription

            ]

        ]);

    }







    /**
     * فعال سازی پلن رایگان
     */
    public function subscribe(
        Request $request
    )
    {


        $request->validate([

            'workspace_plan_id'=>
                'required|exists:workspace_plans,id'

        ]);



        $user =
            $request->user();




        $plan =
            WorkspacePlan::findOrFail(
                $request->workspace_plan_id
            );




        $workspace =
            $this->workspaceService
                ->create(

                    $user,

                    $plan->workspaceType,

                    $plan

                );





        $subscription =
            $this->subscriptionService
                ->subscribe(

                    $user,

                    $workspace,

                    $plan,

                    'free'

                );






        $wallet =
            $this->walletService
                ->initialize(

                    $workspace,

                    $plan->token_limit

                );





        return response()->json([

            'success'=>true,

            'workspace'=>$workspace,

            'subscription'=>$subscription,

            'wallet'=>$wallet

        ]);

    }







    /**
     * شروع پرداخت Workspace
     */
    public function initiatePayment(
        Request $request
    )
    {


        $request->validate([


            'workspace_id'=>
                'required|exists:workspaces,id',



            'workspace_plan_id'=>
                'required|exists:workspace_plans,id'


        ]);




        $user =
            $request->user();




        $workspace =
            $user
            ->workspaces()
            ->where(
                'id',
                $request->workspace_id
            )
            ->firstOrFail();





        $plan =
            WorkspacePlan::findOrFail(
                $request->workspace_plan_id
            );





        /*
        |--------------------------------------------------------------------------
        | Free Plan
        |--------------------------------------------------------------------------
        */

        if($plan->price <= 0)
        {

            return $this->subscribe(
                $request
            );

        }





        /*
        |--------------------------------------------------------------------------
        | Zarinpal
        |--------------------------------------------------------------------------
        */


        $callback =
            route(
                'workspace.payment.callback'
            );





        $result =
            $this->zarinpal
                ->requestPayment(

                    $plan->price,

                    $callback,

                    'خرید '.$plan->display_name,

                    $user->phone

                );





        if(!$result['success'])
        {

            return response()->json([

                'message'=>$result['message']

            ],500);

        }







        $transaction =
            PaymentTransaction::create([


                'user_id'=>
                    $user->id,


                'workspace_id'=>
                    $workspace->id,


                'workspace_plan_id'=>
                    $plan->id,


                'amount'=>
                    $plan->price,


                'authority'=>
                    $result['authority'],


                'status'=>
                    'pending'


            ]);






        return response()->json([


            'payment_url'=>
                $result['payment_url'],


            'authority'=>
                $transaction->authority


        ]);

    }







    /**
     * Callback زرین پال
     */
    public function paymentCallback(
        Request $request
    )
    {


        $authority =
            $request->query(
                'Authority'
            );



        $status =
            $request->query(
                'Status'
            );





        if($status !== 'OK')
        {

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






        $verify =
            $this->zarinpal
                ->verifyPayment(

                    $authority,

                    $transaction->amount

                );






        if(!$verify['success'])
        {


            $transaction->update([

                'status'=>'failed'

            ]);



            Log::error(
                'Workspace Payment Failed',
                [
                    'authority'=>$authority
                ]
            );



            return redirect(
                '/payment/failed'
            );

        }







        $transaction->update([

            'status'=>'paid',

            'ref_id'=>
                $verify['ref_id']

        ]);








        $workspace =
            $transaction->workspace;

        $plan =
            WorkspacePlan::findOrFail(
                $transaction->workspace_plan_id
            );



        // بررسی اشتراک فعال جهت تشخیص ارتقا (Upgrade) یا خرید جدید
        $activeSubscription = $this->subscriptionService->active($workspace);

        if ($activeSubscription) {
            // اگر کاربر از قبل اشتراک فعال دارد، پلن او را ارتقا می‌دهیم
            $this->subscriptionService->upgrade(
                $transaction->user,
                $workspace,
                $plan,
                $verify['ref_id']
            );
        } else {
            // اگر اشتراک فعالی ندارد، اشتراک جدید ثبت می‌کنیم
            $this->subscriptionService->subscribe(
                $transaction->user,
                $workspace,
                $plan,
                $verify['ref_id']
            );

            // مقداردهی اولیه کیف پول برای خرید اولیه
            $this->walletService->initialize(
                $workspace,
                $plan->token_limit
            );
        }



        return redirect(

            '/payment/success?ref_id='
            .$verify['ref_id']

        );

    }







    /**
     * لغو اشتراک
     */
    public function cancel(
        Request $request
    )
    {


        $workspace =
            $this->workspaceService
                ->current(
                    $request->user()
                );




        if($workspace)
        {

            $this->subscriptionService
                ->cancelCurrent(
                    $workspace
                );

        }




        return response()->json([

            'success'=>true,

            'message'=>'Workspace لغو شد'

        ]);

    }


}
