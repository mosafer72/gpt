<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\PaymentTransaction;
use App\Http\Services\ZarinpalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\TokenWallet;

class SubscriptionController extends Controller
{
    protected $zarinpal;

    public function __construct(ZarinpalService $zarinpal)
    {
        $this->zarinpal = $zarinpal;
    }

    // 1. دریافت لیست تمام پلن‌های فعال
    public function plans()
    {

        $plans = Plan::with([
            'models'
        ])
            ->where(
                'is_active',
                true
            )
            ->get();


        return response()->json($plans);

    }

    // 2. دریافت اطلاعات اشتراک فعال کاربر
    public function mySubscription(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'احراز هویت نشده‌اید.'], 401);
        }

        $subscription = $user
            ->activeSubscription()
            ->with([
                'plan.models'
            ])
            ->first();

        if (!$subscription) {
            return response()->json([
                'has_subscription' => false,
                'message' => 'هیچ اشتراک فعالی ندارید.',
            ]);
        }

        return response()->json([
            'has_subscription' => true,
            'subscription' => [
                'id' => $subscription->id,
                'plan' => $subscription->plan,
                'started_at' => $subscription->started_at,
                'expires_at' => $subscription->expires_at,
                'days_remaining' => $subscription->days_remaining,
                'is_valid' => $subscription->isValid(),
            ],
        ]);
    }

    // 3. محاسبه هزینه ارتقا (با احتساب روزهای باقی‌مانده)
    public function calculateUpgrade(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'احراز هویت نشده‌اید.'], 401);
        }

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::findOrFail($request->plan_id);
        $currentSubscription = $user->activeSubscription()->first();

        if ($currentSubscription) {
            $amount = $currentSubscription->getProRatedPrice($newPlan);
        } else {
            $amount = $newPlan->price;
        }

        return response()->json([
            'plan' => $newPlan,
            'amount' => $amount,
            'amount_toman' => (int) ($amount / 10),
            'has_current_subscription' => (bool) $currentSubscription,
            'current_plan' => $currentSubscription?->plan,
        ]);
    }

    // 4. شروع فرآیند پرداخت
    public function initiatePayment(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'احراز هویت نشده‌اید.'], 401);
        }

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::findOrFail($request->plan_id);

        // محاسبه مبلغ
        $currentSubscription = $user->activeSubscription()->first();
        $amount = $currentSubscription
            ? $currentSubscription->getProRatedPrice($newPlan)
            : $newPlan->price;

        // اگر مبلغ صفر است، ارتقا رایگان
        if ($amount <= 0) {
            return $this->upgradeFree($user, $newPlan);
        }

        // ایجاد درخواست پرداخت در زرین‌پال
        $callbackUrl = route('payment.callback'); // باید در web.php یا api.php تعریف شود
        $result = $this->zarinpal->requestPayment(
            $amount,
            $callbackUrl,
            'شارژ اشتراک ' . $newPlan->display_name,
            $user->phone
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }

        // ذخیره تراکنش در دیتابیس
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'amount' => $amount,
            'authority' => $result['authority'],
            'status' => 'pending',
        ]);

        return response()->json([
            'payment_url' => $result['payment_url'],
            'authority' => $result['authority'],
            'amount' => $amount,
            'amount_toman' => (int) ($amount / 10),
        ]);
    }

    // 5. ارتقا رایگان (زمانی که هزینه صفر است)
    private function upgradeFree($user, $newPlan): JsonResponse
    {
        $currentSubscription = $user->activeSubscription()->first();

        // غیرفعال کردن اشتراک قبلی
        if ($currentSubscription) {
            $currentSubscription->update(['is_active' => false]);
        }

        // ایجاد اشتراک جدید
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'started_at' => now(),
            'expires_at' => now()->addDays($newPlan->duration_days),
            'is_active' => true,
            'transaction_id' => 'free_upgrade_' . time(),
        ]);


        return response()->json([
            'success' => true,
            'message' => 'اشتراک شما با موفقیت ارتقا یافت.',
            'subscription' => $subscription->load('plan'),
        ]);
    }

    // 6. بازگشت از درگاه پرداخت (callback)
    public function paymentCallback(Request $request)
    {
        $authority = $request->query('Authority');
        $status = $request->query('Status');

        if ($status !== 'OK') {
            return response()->json([
                'success' => false,
                'message' => 'پرداخت توسط کاربر لغو شد.',
            ]);
        }

        // پیدا کردن تراکنش بر اساس authority
        $transaction = PaymentTransaction::where('authority', $authority)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'تراکنش نامعتبر است.',
            ]);
        }

        // جلوگیری از پرداخت دوباره
        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'این تراکنش قبلاً پرداخت شده است.',
            ]);
        }

        $amount = $transaction->amount;
        $user = $transaction->user;
        $plan = $transaction->plan;

        // تایید پرداخت با زرین‌پال
        $verifyResult = $this->zarinpal->verifyPayment($authority, $amount);

        if (!$verifyResult['success']) {
            $transaction->update(['status' => 'failed']);
            Log::error('Payment verification failed', [
                'authority' => $authority,
                'message' => $verifyResult['message'],
            ]);
            // return response()->json([
            //     'success' => false,
            //     'message' => $verifyResult['message'],
            // ]);
            return redirect()->to('/payment/failed?authority=' . $authority);
        }

        // پرداخت موفق
        $transaction->update([
            'status' => 'paid',
            'ref_id' => $verifyResult['ref_id'],
        ]);

        // غیرفعال کردن اشتراک قبلی
        $user->subscriptions()->where('is_active', true)->update(['is_active' => false]);

        // ایجاد اشتراک جدید
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'started_at' => now(),
            'expires_at' => now()->addDays($plan->duration_days),
            'is_active' => true,
            'transaction_id' => $verifyResult['ref_id'],
        ]);
        $wallet = TokenWallet::firstOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'total_tokens' => 0,
                'used_tokens' => 0,
                'remaining_tokens' => 0,
            ]
        );


        $wallet->update([

            'total_tokens' => $plan->token_limit,

            'remaining_tokens' => $plan->token_limit,

            'used_tokens' => 0

        ]);
        return redirect()->to('/payment/success/?ref_id=' . $subscription['transaction_id'] . '&authority=' . $transaction->transaction);
    }


    public function wallet()
    {
        $user = Auth::user();

        $wallet = TokenWallet::firstOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'total_tokens' => 0,
                'used_tokens' => 0,
                'remaining_tokens' => 0
            ]
        );


        return response()->json([
            "total_tokens" => $wallet->total_tokens,
            "used_tokens" => $wallet->used_tokens,
            "remaining_tokens" => $wallet->remaining_tokens
        ]);
    }
}
