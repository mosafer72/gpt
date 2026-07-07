<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ZarinpalService
{
    protected $client;
    protected $merchantId;
    protected $sandbox;
    protected $apiUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->merchantId = config('services.zarinpal.merchant_id');
        $this->sandbox = config('services.zarinpal.sandbox', true);
        $this->apiUrl = $this->sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment/'
            : 'https://api.zarinpal.com/pg/v4/payment/';
    }

    // درخواست پرداخت
    public function requestPayment($amount, $callbackUrl, $description, $mobile = null)
    {
        $data = [
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'callback_url' => $callbackUrl,
            'description' => $description,
            'metadata' => ['mobile' => $mobile],
        ];

        try {
            $response = $this->client->post($this->apiUrl . 'request.json', [
                'json' => $data,
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['data']['code'] == 100) {
                return [
                    'success' => true,
                    'authority' => $result['data']['authority'],
                    'payment_url' => $this->sandbox
                        ? 'https://sandbox.zarinpal.com/pg/StartPay/' . $result['data']['authority']
                        : 'https://www.zarinpal.com/pg/StartPay/' . $result['data']['authority'],
                ];
            }

            Log::error('Zarinpal request error', $result);
            return ['success' => false, 'message' => $result['data']['message'] ?? 'خطا در ایجاد پرداخت'];

        } catch (\Exception $e) {
            Log::error('Zarinpal request exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ارتباط با درگاه پرداخت'];
        }
    }

    // تایید پرداخت
    public function verifyPayment($authority, $amount)
    {
        $data = [
            'merchant_id' => $this->merchantId,
            'authority' => $authority,
            'amount' => $amount,
        ];

        try {
            $response = $this->client->post($this->apiUrl . 'verify.json', [
                'json' => $data,
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['data']['code'] == 100) {
                return [
                    'success' => true,
                    'ref_id' => $result['data']['ref_id'],
                ];
            }

            Log::error('Zarinpal verify error', $result);
            return ['success' => false, 'message' => $result['data']['message'] ?? 'پرداخت ناموفق'];

        } catch (\Exception $e) {
            Log::error('Zarinpal verify exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در تایید پرداخت'];
        }
    }
}
