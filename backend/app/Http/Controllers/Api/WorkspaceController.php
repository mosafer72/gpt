<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;
use App\Application\Services\WorkspaceService;
use App\Application\Services\WorkspaceSubscriptionService;
use App\Application\Services\TokenWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class WorkspaceController extends Controller
{


    public function __construct(

        protected WorkspaceService $workspaceService,

        protected WorkspaceSubscriptionService $subscriptionService,

        protected TokenWalletService $walletService

    ) {

    }



    public function index()
    {
        $user = Auth::user();


        return response()->json(

            $user
                ->workspaces()
                ->with([

                    'plan',

                    'wallet',

                    'workspaceType',

                ])
                ->get()

        );
    }

    public function products()
    {

        return response()->json(

            WorkspaceType::with(
                'plans.models'
            )
                ->where(
                    'is_active',
                    true
                )
                ->get()

        );

    }





    public function myWorkspaces()
    {

        $user = Auth::user();


        return response()->json(

            $user
                ->workspaces()
                ->with([

                    'plan',

                    'wallet',

                    'workspaceType',

                ])
                ->get()

        );

    }

    public function show($id)
    {
        $user = Auth::user();


        $workspace = $user
            ->workspaces()
            ->with([

                'workspaceType',

                'plan.models.provider',

                'wallet',

                'plan.models.provider'

            ])
            ->where(
                'workspaces.id',
                $id
            )
            ->firstOrFail();



        return response()->json([
            'success' => true,
            'workspace' => $workspace
        ]);

    }
    public function models($id)
    {
        $user = Auth::user();

        $workspace = $user
            ->workspaces()
            ->with([
                'plan.models.provider'
            ])
            ->where('workspaces.id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'workspace' => $workspace->only([
                'id',
                'name',
            ]),
            'plan' => $workspace->plan,
            'models' => $workspace->plan->models,
        ]);
    }
    public function plans($workspaceTypeId)
    {
        $plans = WorkspacePlan::where(
            'workspace_type_id',
            $workspaceTypeId
        )
            ->where(
                'is_active',
                true
            )
            ->with('models')
            ->get();


        return response()->json([

            'success' => true,

            'data' => $plans

        ]);
    }
    public function createFree(Request $request)
    {

        $request->validate([

            'plan_id' => 'required|exists:workspace_plans,id'

        ]);


        $user = Auth::user();


        $plan = WorkspacePlan::findOrFail(
            $request->plan_id
        );




        /*
        |--------------------------------------------------------------------------
        | Create Workspace
        |--------------------------------------------------------------------------
        */

        $workspace = $this->workspaceService
            ->create(

                $user,

                $plan->workspaceType,

                $plan

            );






        /*
        |--------------------------------------------------------------------------
        | Create Subscription
        |--------------------------------------------------------------------------
        */

        $subscription = $this->subscriptionService
            ->subscribe(

                $user,

                $workspace,

                $plan,

                'free'

            );






        /*
        |--------------------------------------------------------------------------
        | Initialize Wallet
        |--------------------------------------------------------------------------
        */

        $wallet = $this->walletService
            ->initialize(

                $workspace,

                $plan->token_limit

            );






        return response()->json([


            'workspace' => $workspace
                ->load([
                    'workspaceType',
                    'plan'
                ]),


            'subscription' => $subscription,


            'wallet' => $wallet


        ]);

    }

    public function subscription($id)
    {
        $user = Auth::user();


        $workspace = $user
            ->workspaces()
            ->where(
                'workspaces.id',
                $id
            )
            ->firstOrFail();



        $subscription = $workspace
            ->activeSubscription();



        if (!$subscription) {

            return response()->json([

                'has_subscription' => false

            ]);

        }



        return response()->json([

            'has_subscription' => true,


            'subscription' => $subscription
                ->load([
                    'workspacePlan.models'
                ])

        ]);

    }

}
