<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;

use App\Application\Services\WorkspaceService;
use App\Http\Services\SmsIrService;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;



class AuthController extends Controller
{


    public function __construct(

        protected SmsIrService $smsService,

        protected WorkspaceService $workspaceService

    ) {}






    public function sendLoginCode(
        Request $request
    ): JsonResponse {


        $request->validate([

            'phone'=>'required|string|regex:/^09[0-9]{9}$/'

        ]);



        $user = User::where(
            'phone',
            $request->phone
        )->first();



        if(!$user){

            return response()->json([

                'message'=>'کاربر یافت نشد'

            ],404);

        }



        return $this->sendCode($user);

    }









    public function sendRegisterCode(
        Request $request
    ): JsonResponse {


        $request->validate([

            'phone'=>'required|string|regex:/^09[0-9]{9}$/'

        ]);



        $user = User::firstOrCreate(

            [

                'phone'=>$request->phone

            ],

            [

                'name'=>'User'

            ]

        );



        return $this->sendCode($user);

    }









    protected function sendCode(
        User $user
    ): JsonResponse {


        $code = random_int(
            100000,
            999999
        );



        $user->update([


            'verification_code'=>
                Hash::make($code),



            'verification_code_expires_at'=>
                now()->addMinutes(5)

        ]);




        /*
        |--------------------------------------------------------------------------
        | SMS
        |--------------------------------------------------------------------------
        */

        // فعال سازی بعد از تنظیم sms.ir

        $this->smsService->sendVerificationCode(
            $user->phone,
            $code
        );





        return response()->json([


            'message'=>'کد ارسال شد'


            // فقط محیط توسعه
            // 'code'=>$code


        ]);

    }









    public function verifyCode(
        Request $request
    ): JsonResponse {


        $request->validate([


            'phone'=>
                'required|string',


            'code'=>
                'required|string|size:6'


        ]);




        $user = User::where(

            'phone',

            $request->phone

        )
        ->firstOrFail();






        if(

            !$user->verification_code_expires_at

            ||

            $user->verification_code_expires_at < now()

        ){

            return response()->json([

                'message'=>'کد منقضی شده است'

            ],422);

        }








        if(

            !Hash::check(

                $request->code,

                $user->verification_code

            )

        ){

            return response()->json([

                'message'=>'کد اشتباه است'

            ],422);

        }








        $user->update([


            'verification_code'=>null,


            'verification_code_expires_at'=>null


        ]);





        $user->markPhoneAsVerified();







        /*
        |--------------------------------------------------------------------------
        | Default Workspace
        |--------------------------------------------------------------------------
        */


        $this->createDefaultWorkspace(
            $user
        );







        $token =
            $user
                ->createToken(
                    'auth_token'
                )
                ->plainTextToken;







        return response()->json([


            'message'=>'ورود موفق',



            'user'=>$user->only([

                'id',

                'name',

                'phone',

                'role'

            ]),



            'token'=>$token


        ]);

    }









    private function createDefaultWorkspace(
        User $user
    ): void {


        if(

            $user
                ->workspaces()
                ->exists()

        ){

            return;

        }






        $type =
            WorkspaceType::where(

                'key',

                'general'

            )
            ->first();





        $plan =
            WorkspacePlan::where(

                'name',

                'general-basic'

            )
            ->first();







        if(

            !$type ||

            !$plan

        ){

            return;

        }







        $this->workspaceService
            ->create(

                user:$user,

                type:$type,

                plan:$plan

            );


    }


}
