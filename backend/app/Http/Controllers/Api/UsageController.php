<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usage;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\Auth;


class UsageController extends Controller
{


    public function index(Request $request)
    {

        $user = Auth::user();


        $usage = Usage::where(
            'user_id',
            $user->id
        )
        ->latest()
        ->get();



        return response()->json([

            'success'=>true,

            'data'=>$usage

        ]);

    }





    public function today(Request $request)
    {

        $user = Auth::user();


        $usage = Usage::where(

            'user_id',

            $user->id

        )
        ->whereDate(

            'created_at',

            now()->toDateString()

        )
        ->get();



        $totalTokens = $usage->sum(
            'total_tokens'
        );



        $promptTokens = $usage->sum(
            'prompt_tokens'
        );



        $completionTokens = $usage->sum(
            'completion_tokens'
        );




        return response()->json([


            'success'=>true,


            'data'=>[


                'date'=>now()->toDateString(),


                'prompt_tokens'=>$promptTokens,


                'completion_tokens'=>$completionTokens,


                'total_tokens'=>$totalTokens,


                'requests'=>$usage->count()


            ]

        ]);

    }






    public function show($id)
    {


        $usage = Usage::where(

            'user_id',

            Auth::id()

        )
        ->findOrFail($id);



        return response()->json([

            'success'=>true,

            'data'=>$usage

        ]);

    }






    public function summary(Request $request)
    {


        $user = Auth::user();


        $usage = Usage::where(

            'user_id',

            $user->id

        );



        if($request->filled('from'))
        {

            $usage->whereDate(

                'created_at',

                '>=',

                $request->from

            );

        }



        if($request->filled('to'))
        {

            $usage->whereDate(

                'created_at',

                '<=',

                $request->to

            );

        }



        $usage = $usage->get();




        return response()->json([


            'success'=>true,


            'data'=>[


                'requests'=>$usage->count(),


                'prompt_tokens'=>$usage->sum(
                    'prompt_tokens'
                ),


                'completion_tokens'=>$usage->sum(
                    'completion_tokens'
                ),


                'total_tokens'=>$usage->sum(
                    'total_tokens'
                )


            ]

        ]);


    }



}
