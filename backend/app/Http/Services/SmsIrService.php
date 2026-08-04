<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsIrService
{
    protected $apiKey;
    protected $templateId;

    public function __construct()
    {
        $this->apiKey = config('services.smsir.api_key');
        $this->templateId = config('services.smsir.template_id');
    }

    /**
     * ارسال کد تایید از طریق sms.ir
     * @param string $phone
     * @param string $code
     * @return bool
     */
    public function sendVerificationCode(string $phone, string $code): bool
    {
        if (empty($this->apiKey) || empty($this->templateId)) {
            \Log::error('SMS.ir credentials not set.');
            return false;
        }

        // حذف صفر ابتدایی (مطابق با فرمت sms.ir)
        $mobile = ltrim($phone, '0');

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.sms.ir/v1/send/verify', [
                'mobile' => $mobile,
                'templateId' => (int) $this->templateId,
                'parameters' => [
                    [
                        'name' => 'Code',
                        'value' => (string) $code,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // بررسی وضعیت موفقیت
                if (isset($data['status']) && $data['status'] == 1) {
                    \Log::info("SMS.ir sent to {$phone}");
                    return true;
                }

                \Log::error('SMS.ir response: ' . json_encode($data));
                return false;
            }

            \Log::error('SMS.ir HTTP error: ' . $response->status() . ' - ' . $response->body());
            return false;

        } catch (\Exception $e) {
            \Log::error('SMS.ir exception: ' . $e->getMessage());
            return false;
        }
    }
}
