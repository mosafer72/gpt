<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Services\UsageService;
use Illuminate\Http\Request;


class UsageController extends Controller
{


    public function __construct(

        protected UsageService $usageService

    ) {

    }





    public function index(Request $request)
    {

        return response()->json([

            'success' => true,

            'data' => $this->usageService
                ->all(
                    $request->user()
                )

        ]);

    }





    public function today(Request $request)
    {

        $user = $request->user();


        $workspace = $user
            ->workspaces()
            ->where(
                'is_active',
                true
            )
            ->first();



        if (!$workspace) {

            return response()->json([

                'message' => 'Workspace فعال ندارید'

            ], 404);

        }



        $usage =
            $this->usageService
                ->today(
                    $workspace
                );



        return response()->json([

            'success' => true,

            'data' => $usage

        ]);

    }





    public function show(
        Request $request,
        $id
    ) {

        return response()->json([

            'success' => true,

            'data' => $this->usageService
                ->find(
                    $request->user(),
                    $id
                )

        ]);

    }





    public function summary(Request $request)
    {

        return response()->json([

            'success' => true,

            'data' => $this->usageService
                ->summary(

                    $request->user(),

                    $request->all()

                )

        ]);

    }


}
