<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StkPushApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_initiates_stk_push_and_returns_checkout_ids(): void
    {
        config([
            'mpesa.consumer_key' => 'consumer-key',
            'mpesa.consumer_secret' => 'consumer-secret',
            'mpesa.shortcode' => '174379',
            'mpesa.passkey' => 'passkey',
            'mpesa.callback_url' => 'https://paygate.test/api/mpesa/stk-callback/secret',
        ]);

        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'daraja-token'], 200),
            'sandbox.safaricom.co.ke/*' => Http::response([
                'MerchantRequestID' => '12345',
                'CheckoutRequestID' => 'ws_CO_678',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);

        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);
        $apiKey = ApiKey::create([
            'merchant_id' => $merchant->id,
            'name' => 'Test Key',
            'environment' => 'sandbox',
            'publishable_key' => 'pay_pk_test',
            'secret_key_hash' => bcrypt('pay_sk_test'),
            'secret_key_prefix' => substr('pay_sk_test', 0, 10),
            'secret_key_last_four' => substr('pay_sk_test', -4),
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'x-api-key' => 'pay_sk_test',
            'Idempotency-Key' => 'idem-key-123',
        ])->postJson('/api/v1/transactions/push-stk', [
            'phone' => '0712345678',
            'amount' => 100,
            'reference' => 'ORDER-100',
            'description' => 'Test payment',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['transaction_id', 'status', 'checkout_request_id', 'merchant_request_id', 'customer_message']);
    }
}
