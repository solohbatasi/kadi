<?php

namespace App\Services\Mpesa;

use App\Models\Payout;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class B2CPayoutService
{
    public function __construct(protected DarajaAuthService $auth)
    {
    }

    public function send(Payout $payout): array
    {
        $config = config('mpesa.b2c');

        if ($config['fake'] ?? true) {
            if (($config['fake_result'] ?? 'success') === 'failure') {
                throw new RuntimeException('Fake B2C payout failure.');
            }

            return [
                'accepted' => true,
                'fake' => true,
                'conversation_id' => 'fake_'.$payout->public_id,
                'originator_conversation_id' => 'fake_origin_'.$payout->public_id,
                'result_code' => '0',
                'result_description' => 'Fake payout accepted.',
            ];
        }

        foreach (['initiator_name', 'security_credential', 'result_url', 'timeout_url'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException('M-Pesa B2C credentials are not configured.');
            }
        }

        $response = Http::withToken($this->auth->getAccessToken(config('mpesa.environment', 'sandbox')))
            ->acceptJson()
            ->timeout(config('mpesa.timeout', 30))
            ->post($this->baseUrl().'/mpesa/b2c/v1/paymentrequest', [
                'InitiatorName' => $config['initiator_name'],
                'SecurityCredential' => $config['security_credential'],
                'CommandID' => $config['command_id'] ?? 'BusinessPayment',
                'Amount' => $payout->amount,
                'PartyA' => config('mpesa.shortcode'),
                'PartyB' => $payout->phone,
                'Remarks' => 'Merchant payout',
                'QueueTimeOutURL' => $config['queue_timeout_url'] ?: $config['timeout_url'],
                'ResultURL' => $config['result_url'],
                'Occasion' => $payout->public_id,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('M-Pesa B2C payout request failed.');
        }

        $body = $response->json();

        return [
            'accepted' => true,
            'conversation_id' => $body['ConversationID'] ?? null,
            'originator_conversation_id' => $body['OriginatorConversationID'] ?? null,
            'result_code' => $body['ResponseCode'] ?? null,
            'result_description' => $body['ResponseDescription'] ?? null,
            'metadata' => ['response' => $body],
        ];
    }

    protected function baseUrl(): string
    {
        return config('mpesa.environment') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }
}
