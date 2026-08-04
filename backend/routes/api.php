<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\PresentationController;
use App\Http\Controllers\Api\WorkspaceSubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ModelController;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\TokenWalletController;
use App\Http\Controllers\Api\PaperController;
use App\Http\Controllers\Api\MessageExportController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspacePlanController;
use App\Http\Controllers\Api\SubscriptionController;


/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/


Route::post(
    '/auth/send-login-code',
    [AuthController::class, 'sendLoginCode']
);


Route::post(
    '/auth/send-register-code',
    [AuthController::class, 'sendRegisterCode']
);


Route::post(
    '/auth/verify-code',
    [AuthController::class, 'verifyCode']
);


Route::post(
    '/auth/resend-code',
    [AuthController::class, 'resendCode']
);



/*
|--------------------------------------------------------------------------
| Protected User Routes
|--------------------------------------------------------------------------
*/


Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/workspaces/{workspaceId}/documents',
        [DocumentController::class, 'index']
    );

    Route::post(
        '/workspaces/{workspace}/chats',
        [ChatController::class, 'store']
    );


    Route::post(
        '/workspaces/{workspaceId}/documents',
        [DocumentController::class, 'store']
    );



    Route::delete(
        '/workspaces/{workspaceId}/documents/{documentId}',
        [DocumentController::class, 'destroy']
    );




    Route::get('/workspaces/{workspaceId}/documents/{document}', [DocumentController::class, 'show']);

    Route::put('/workspaces/{workspaceId}/documents/{document}', [DocumentController::class, 'update']);
    Route::get(
        '/workspaces/{workspace}/library/{document}/overview',
        [
            DocumentController::class,
            'overview'
        ]
    );


    Route::prefix('workspaces/{workspace}')
        ->group(function () {

            Route::get(
                'chats',
                [ChatController::class, 'index']
            );

            Route::get(
                'check-access',
                [ChatController::class, 'checkAccess']
            );


            Route::post(
                'chats',
                [ChatController::class, 'store']
            );


            Route::get(
                'chats/{chat}',
                [ChatController::class, 'show']
            );


            Route::delete(
                'chats/{chat}',
                [ChatController::class, 'destroy']
            );

        });

    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */


    Route::get(
        '/user',
        function (Request $request) {

            return $request->user();

        }
    );


    Route::post(
        '/auth/logout',
        [AuthController::class, 'logout']
    );



    Route::get(
        '/user/status',
        [AuthController::class, 'status']
    );




    /*
    |--------------------------------------------------------------------------
    | Workspace
    |--------------------------------------------------------------------------
    |
    | کاربر Workspace های خودش را می‌بیند
    |
    */


    Route::get(
        '/workspaces',
        [WorkspaceController::class, 'index']
    );


    Route::get(
        '/workspaces/{workspace}',
        [WorkspaceController::class, 'show']
    );



    /*
    |--------------------------------------------------------------------------
    | Workspace Subscription
    |--------------------------------------------------------------------------
    */


    Route::get(
        '/workspace-subscriptions',
        [
            WorkspaceController::class,
            'subscriptions'
        ]
    );



    /*
    |--------------------------------------------------------------------------
    | Chats
    |--------------------------------------------------------------------------
    |
    | همه چت‌ها متعلق به Workspace هستند
    |
    */


    Route::apiResource(
        '/chats',
        ChatController::class
    );



    Route::get(
        '/chats/{chat}/messages',
        [
            ChatController::class,
            'messages'
        ]
    );



    /*
    |--------------------------------------------------------------------------
    | Stream Chat
    |--------------------------------------------------------------------------
    */


    Route::post(
        '/chats/stream',
        [
            ChatController::class,
            'store'
        ]
    );




    /*
    |--------------------------------------------------------------------------
    | Papers / Files
    |--------------------------------------------------------------------------
    */


    Route::post(
        '/papers/upload',
        [
            PaperController::class,
            'upload'
        ]
    );


    Route::post(
        '/files/upload',
        [
            PaperController::class,
            'upload'
        ]
    );





    /*
    |--------------------------------------------------------------------------
    | Usage
    |--------------------------------------------------------------------------
    */


    Route::get(
        '/usage',
        [
            UsageController::class,
            'index'
        ]
    );


    Route::get(
        '/usage/today',
        [
            UsageController::class,
            'today'
        ]
    );


    Route::get(
        '/usage/summary',
        [
            UsageController::class,
            'summary'
        ]
    );


    Route::get(
        '/usage/{id}',
        [
            UsageController::class,
            'show'
        ]
    );





    /*
    |--------------------------------------------------------------------------
    | Token Wallet
    |--------------------------------------------------------------------------
    */


    Route::get(
        '/token-wallet',
        [
            TokenWalletController::class,
            'show'
        ]
    );





    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */


    Route::get(
        '/models',
        [
            ModelController::class,
            'index'
        ]
    );


    Route::post(
        '/workspace/upgrade',
        [
            WorkspaceSubscriptionController::class,
            'upgrade'
        ]
    );



    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */


    Route::get(
        '/messages/{message}/export',
        [
            MessageExportController::class,
            'export'
        ]
    );
    /*
    |--------------------------------------------------------------------------
    | Workspace
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/workspace/plans',
        [WorkspaceSubscriptionController::class, 'plans']
    );


    Route::get(
        '/workspace/my',
        [WorkspaceSubscriptionController::class, 'myWorkspace']
    );


    Route::post(
        '/workspace/subscribe',
        [WorkspaceSubscriptionController::class, 'subscribe']
    );

    Route::post(
        '/subscription/payment',
        [SubscriptionController::class, 'initiatePayment']
    );

    Route::post(
        '/workspace/cancel',
        [WorkspaceSubscriptionController::class, 'cancel']
    );

    Route::get(
        '/workspaces',
        [WorkspaceController::class, 'index']
    );
    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    );

    Route::get(
        '/workspaces/{key}',
        [WorkspaceController::class, 'show']
    );


    Route::get(
        '/workspaces/{id}/plans',
        [WorkspaceController::class, 'plans']
    );

    Route::get(
        '/workspace/{id}/subscription',
        [
            WorkspaceController::class,
            'subscription'
        ]
    );
    Route::get(
        '/workspace/{id}/models',
        [WorkspaceController::class, 'models']
    );
    Route::get(
        '/workspace-types/{id}/workspace_plans',
        [WorkspaceController::class, 'workspace_plans']
    );
    Route::post(
        '/workspace/initiate-payment',
        [WorkspaceSubscriptionController::class, 'initiatePayment']
    );

    Route::post(
        '/workspace/subscription/payment',
        [
            WorkspaceSubscriptionController::class,
            'initiatePayment'
        ]
    );
    Route::get(
        '/my-workspace-subscription',
        [WorkspaceSubscriptionController::class, 'mySubscription']
    );

    /*
    |--------------------------------------------------------------------------
    | Subscription Payment
    |--------------------------------------------------------------------------
    */

});





/*
|--------------------------------------------------------------------------
| Payment Callback
|--------------------------------------------------------------------------
*/


Route::get(
    '/payment/callback',
    [
        WorkspaceSubscriptionController::class,
        'paymentCallback'
    ]
)
    ->name('workspace.payment.callback');








Route::middleware('auth:sanctum')
    ->prefix('workspaces/{workspace}/presentations')
    ->group(function () {


        Route::get(
            '/',
            [PresentationController::class, 'index']
        );


        Route::post(
            '/',
            [PresentationController::class, 'store']
        );


        Route::get(
            '/{document}/download',
            [PresentationController::class, 'download']
        );


    });


/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

include_once("admin.php");
