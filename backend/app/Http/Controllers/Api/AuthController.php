<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Services\SmsIrService;
use Cache;
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
    // متد ورود
    public function sendLoginCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        $phone = $validated['phone'];

        // محدودیت ارسال
        $cacheKey = 'sms_send_' . $phone;
        if (Cache::has($cacheKey)) {
            return response()->json([
                'error' => 'لطفاً یک دقیقه بعد دوباره تلاش کنید.'
            ], 429);
        }

        // بررسی وجود کاربر
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json([
                'error' => 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.'
            ], 404);
        }

        // تولید و ذخیره کد
        $code = (string) random_int(100000, 999999);
        $user->update([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(5),
        ]);

        Cache::put($cacheKey, true, now()->addMinute());

        // ارسال پیامک
        $sent = $this->smsService->sendVerificationCode($phone, $code);
        if (!$sent) {
            return response()->json([
                'error' => 'ارسال پیامک با مشکل مواجه شد. لطفاً مجدداً تلاش کنید.'
            ], 500);
        }

        return response()->json([
            'message' => 'کد تایید برای ورود ارسال شد.',
        ]);
    }

    // متد ثبت‌نام
    public function sendRegisterCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'name'  => 'required|string|min:2|max:100',
        ]);

        $phone = $validated['phone'];
        $name  = trim($validated['name']);

        // محدودیت ارسال
        $cacheKey = 'sms_send_' . $phone;
        if (Cache::has($cacheKey)) {
            return response()->json([
                'error' => 'لطفاً یک دقیقه بعد دوباره تلاش کنید.'
            ], 429);
        }

        // ایجاد یا بروزرسانی کاربر با نام جدید
        $user = User::updateOrCreate(
            ['phone' => $phone],
            ['name' => $name]
        );
        // اگر کاربر قبلاً وجود داشت، نام را به‌روز می‌کنیم
        if (!$user->wasRecentlyCreated) {
            $user->update(['name' => $name]);
        }

        // تولید و ذخیره کد
        $code = (string) random_int(100000, 999999);
        $user->update([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(5),
        ]);

        Cache::put($cacheKey, true, now()->addMinute());

        // ارسال پیامک
        $sent = $this->smsService->sendVerificationCode($phone, $code);
        if (!$sent) {
            return response()->json([
                'error' => 'ارسال پیامک با مشکل مواجه شد. لطفاً مجدداً تلاش کنید.'
            ], 500);
        }

        return response()->json([
            'message' => 'کد تایید برای ثبت‌نام ارسال شد.',
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
