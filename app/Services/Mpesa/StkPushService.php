<?php

namespace App\Services\Mpesa;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StkPushService
{
    public function __construct(protected DarajaAuthService $auth)
    {
    }

    public function requestStkPush(Transaction $transaction, string $phone, int $amount, string $idempotencyKey, string $environment): array
    {
        $config = config('mpesa');

        $token = $this->auth->getAccessToken($environment);
        $url = $this->buildBaseUrl($environment).'/mpesa/stkpush/v1/processrequest';
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($config['shortcode'].$config['passkey'].$timestamp);

        $payload = [
            'BusinessShortCode' => $config['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $config['shortcode'],
            'PhoneNumber' => $phone,
            'CallBackURL' => $config['callback_url'],
            'AccountReference' => $transaction->reference ?? $transaction->public_id,
            'TransactionDesc' => $transaction->description ?? 'Kadi STK Push',
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout($config['timeout'] ?? 30)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('M-Pesa STK Push request failed.');
        }

        $body = $response->json();

        if (($body['ResponseCode'] ?? null) !== '0' && ($body['ResponseCode'] ?? null) !== 0) {
            throw new RuntimeException(sprintf('M-Pesa STK Push error: %s', $body['ResponseDescription'] ?? 'Unknown response.'));
        }

        return [
            'merchant_request_id' => $body['MerchantRequestID'] ?? null,
            'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
            'response_code' => $body['ResponseCode'] ?? null,
            'response_description' => $body['ResponseDescription'] ?? null,
            'customer_message' => $body['CustomerMessage'] ?? null,
        ];
    }

    protected function buildBaseUrl(string $environment): string
    {
        return $environment === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }
}
