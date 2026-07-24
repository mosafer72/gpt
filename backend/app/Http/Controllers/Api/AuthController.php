<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceSubscription;
use App\Models\TokenWallet;
use App\Http\Services\SmsIrService;

use Cache;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{

    protected $smsService;


    public function __construct(
        SmsIrService $smsService
    ) {
        $this->smsService = $smsService;
    }





    /*
    |--------------------------------------------------------------------------
    | Send Login Code
    |--------------------------------------------------------------------------
    */

    public function sendLoginCode(Request $request): JsonResponse
    {

        $validated = $request->validate([

            'phone' => 'required|string|regex:/^09[0-9]{9}$/',

        ]);


        $phone = $validated['phone'];


        $cacheKey = 'sms_send_' . $phone;


        if (Cache::has($cacheKey)) {

            return response()->json([

                'error' => 'لطفاً یک دقیقه بعد دوباره تلاش کنید.'

            ], 429);

        }



        $user = User::where(
            'phone',
            $phone
        )->first();



        if (!$user) {

            return response()->json([

                'error' => 'کاربری با این شماره یافت نشد.'

            ], 404);

        }




        $code = random_int(
            100000,
            999999
        );



        $user->update([

            'verification_code' => Hash::make($code),

            'verification_code_expires_at' => now()
                ->addMinutes(5)

        ]);



        Cache::put(
            $cacheKey,
            true,
            now()->addMinute()
        );



        $sent = $this->smsService
            ->sendVerificationCode(
                $phone,
                $code
            );



        if (!$sent) {

            return response()->json([

                'error' => 'ارسال پیامک ناموفق بود.'

            ], 500);

        }



        return response()->json([

            'message' => 'کد ورود ارسال شد.'

        ]);

    }







    /*
    |--------------------------------------------------------------------------
    | Send Register Code
    |--------------------------------------------------------------------------
    */

    public function sendRegisterCode(Request $request): JsonResponse
    {

        $validated = $request->validate([

            'phone' => 'required|string|regex:/^09[0-9]{9}$/',

            'name' => 'required|string|min:2|max:100'

        ]);



        $phone = $validated['phone'];

        $name = trim($validated['name']);



        $cacheKey = 'sms_send_' . $phone;



        if (Cache::has($cacheKey)) {

            return response()->json([

                'error' => 'لطفاً یک دقیقه بعد دوباره تلاش کنید.'

            ], 429);

        }




        $user = User::updateOrCreate(

            [

                'phone' => $phone

            ],

            [

                'name' => $name

            ]

        );




        $code = random_int(
            100000,
            999999
        );



        $user->update([

            'verification_code' => Hash::make($code),

            'verification_code_expires_at' => now()
                ->addMinutes(5)

        ]);



        Cache::put(
            $cacheKey,
            true,
            now()->addMinute()
        );




        $sent = $this->smsService
            ->sendVerificationCode(
                $phone,
                $code
            );



        if (!$sent) {

            return response()->json([

                'error' => 'ارسال پیامک ناموفق بود.'

            ], 500);

        }



        return response()->json([

            'message' => 'کد ثبت نام ارسال شد.'

        ]);

    }







    /*
    |--------------------------------------------------------------------------
    | Verify Code
    |--------------------------------------------------------------------------
    */

    public function verifyCode(Request $request): JsonResponse
    {

        $request->validate([

            'phone' => 'required|string|regex:/^09[0-9]{9}$/',

            'code' => 'required|string|size:6'

        ]);




        $user = User::where(
            'phone',
            $request->phone
        )->first();



        if (!$user) {

            return response()->json([

                'error' => 'کاربر یافت نشد.'

            ], 404);

        }




        if (
            !$user->verification_code_expires_at
            ||
            $user->verification_code_expires_at < now()
        ) {

            return response()->json([

                'error' => 'کد منقضی شده است.'

            ], 422);

        }




        if (
            !Hash::check(
                $request->code,
                $user->verification_code
            )
        ) {

            return response()->json([

                'error' => 'کد اشتباه است.'

            ], 422);

        }




        $user->markPhoneAsVerified();



        /*
        |--------------------------------------------------------------------------
        | Create Default Workspace
        |--------------------------------------------------------------------------
        */


        $this->createDefaultWorkspace($user);





        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;



        return response()->json([

            'message' => 'ورود موفق',

            'user' => $user->only([

                'id',
                'phone',
                'name',
                'role'

            ]),

            'token' => $token

        ]);

    }







    /*
    |--------------------------------------------------------------------------
    | Create Workspace
    |--------------------------------------------------------------------------
    */

    private function createDefaultWorkspace(User $user)
    {


        if (
            $user->workspaces()->exists()
        ) {

            return;

        }



        $type = WorkspaceType::where(
            'key',
            'student'
        )->first();



        $plan = WorkspacePlan::where(
            'name',
            'student-basic'
        )->first();



        if (!$type || !$plan) {

            return;

        }




        $workspace = Workspace::create([

            'user_id' => $user->id,

            'workspace_type_id' => $type->id,

            'name' => $type->name,

            'is_active' => true

        ]);





        TokenWallet::create([

            'workspace_id' => $workspace->id,

            'total_tokens' => $plan->token_limit,

            'used_tokens' => 0,

            'remaining_tokens' => $plan->token_limit

        ]);





        WorkspaceSubscription::create([

            'user_id' => $user->id,

            'workspace_id' => $workspace->id,

            'workspace_plan_id' => $plan->id,

            'started_at' => now(),

            'expires_at' => now()
                ->addDays(
                    $plan->duration_days
                ),

            'is_active' => true,

            'transaction_id' =>
                'free_register_' . $user->id

        ]);

    }








    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request): JsonResponse
    {

        $request
            ->user()
            ->currentAccessToken()
            ->delete();



        return response()->json([

            'message' => 'خروج موفق'

        ]);

    }







    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();


        $hasSubscription = $user
            ->workspaces()
            ->whereHas('workspaceSubscriptions', function ($query) {

                $query
                    ->where('is_active', true)
                    ->where(
                        'expires_at',
                        '>',
                        now()
                    );

            })
            ->exists();



        return response()->json([

            'has_subscription' => $hasSubscription

        ]);
    }

}
