<?php

namespace App\Http\Services;

use Kavenegar\KavenegarApi;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $api;

    public function __construct()
    {
        $this->api = new KavenegarApi(env('KAVENEGAR_API_KEY'));
    }

    public function sendVerificationCode(string $phone, string $code): bool
    {
        try {
            $message = "کد تایید شما: {$code}\n\n" . config('app.name');

            $result = $this->api->Send(
                env('KAVENEGAR_SENDER_NUMBER'),
                $phone,
                $message
            );

            Log::info("SMS sent to {$phone}", ['result' => $result]);
            return true;

        } catch (\Exception $e) {
            Log::error("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }
}
