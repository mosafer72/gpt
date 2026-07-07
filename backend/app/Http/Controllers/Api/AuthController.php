<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Services\SmsIrService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $smsService;

    public function __construct(SmsIrService $smsService)
    {
        $this->smsService = $smsService;
    }

    // ۱. ارسال کد تایید
    public function sendVerificationCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        $phone = $request->phone;

        // پیدا کردن یا ایجاد کاربر
        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['phone' => $phone]
        );

        // تولید کد ۶ رقمی
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // ذخیره کد با زمان انقضا (۵ دقیقه)
        $user->update([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(5),
        ]);

        // ارسال پیامک از طریق sms.ir
        $sent = $this->smsService->sendVerificationCode($phone, $code);

        if (!$sent) {
            // در محیط توسعه، کد را در پاسخ برگردان (برای تست)
            if (app()->environment('local')) {
                return response()->json([
                    'message' => 'کد تایید ارسال شد (برای تست: ' . $code . ')',
                    'debug_code' => $code,
                ]);
            }

            return response()->json([
                'error' => 'ارسال پیامک با مشکل مواجه شد. لطفاً مجدداً تلاش کنید.'
            ], 500);
        }

        return response()->json([
            'message' => 'کد تایید به شماره شما ارسال شد.',
            'phone' => $phone,
        ]);
    }

    // ۲. تایید کد و ورود
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'کاربر یافت نشد.'], 404);
        }

        // بررسی انقضا
        if ($user->verification_code_expires_at < now()) {
            return response()->json(['error' => 'کد تایید منقضی شده است. دوباره درخواست کنید.'], 422);
        }

        // بررسی کد
        if (!Hash::check($request->code, $user->verification_code)) {
            return response()->json(['error' => 'کد تایید اشتباه است.'], 422);
        }

        // تایید شماره
        $user->markPhoneAsVerified();

        // تولید توکن (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'ورود موفقیت‌آمیز بود.',
            'user' => $user->only(['id', 'phone', 'name']),
            'token' => $token,
        ]);
    }

    // ۳. ارسال مجدد کد
    public function resendCode(Request $request): JsonResponse
    {
        return $this->sendVerificationCode($request);
    }

    // ۴. خروج
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'خروج موفقیت‌آمیز بود.']);
    }


    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $hasSubscription = $user->hasActiveSubscription();
        $dailyLimit = $user->dailyMessageLimit();
        $todayCount = $user->todayMessagesCount();
        $remaining = max(0, $dailyLimit - $todayCount);

        return response()->json([
            'has_subscription' => $hasSubscription,
            'daily_message_limit' => $dailyLimit,
            'today_messages_count' => $todayCount,
            'remaining_messages' => $remaining,
        ]);
    }
}
