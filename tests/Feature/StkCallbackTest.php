<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StkCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_with_invalid_secret_returns_401(): void
    {
        $response = $this->postJson('/api/mpesa/callback', [
            'Body' => [],
        ], ['x-callback-secret' => 'wrong']);

        $response->assertStatus(401);
    }

    public function test_callback_with_unknown_checkout_request_id_is_stored_and_returns_200(): void
    {
        $response = $this->withHeaders([
            'x-callback-secret' => config('mpesa.callback_secret', 'secret'),
        ])->postJson('/api/mpesa/callback', [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'unknown',
                    'MerchantRequestID' => '999',
                    'ResultCode' => 1,
                    'ResultDesc' => 'Failed',
                    'CustomerMessage' => 'Failure',
                    'CallbackMetadata' => ['Item' => []],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_callback_credits_wallet_only_once_for_successful_payment(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);
        $merchant->wallet()->create(['public_id' => 'wal_test', 'available_balance' => 0, 'pending_balance' => 0, 'currency' => 'KES']);

        $transaction = Transaction::create([
            'merchant_id' => $merchant->id,
            'public_id' => 'txn_test',
            'type' => 'stk_push',
            'direction' => 'credit',
            'environment' => 'sandbox',
            'phone' => '254712345678',
            'amount' => 100,
            'currency' => 'KES',
            'commission_amount' => 2,
            'provider_fee' => 0,
            'net_amount' => 98,
            'status' => 'pending',
            'reference' => 'ORDER-100',
            'description' => 'Test payment',
            'idempotency_key' => 'idem-key-123',
            'mpesa_checkout_request_id' => 'ws_CO_678',
        ]);

        $headers = ['x-callback-secret' => config('mpesa.callback_secret', 'secret')];
        $payload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_678',
                    'MerchantRequestID' => '12345',
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CustomerMessage' => 'Success. Request accepted for processing',
                    'CallbackMetadata' => ['Item' => []],
                ],
            ],
        ];

        $this->withHeaders($headers)->postJson('/api/mpesa/callback', $payload)->assertStatus(200);
        $this->withHeaders($headers)->postJson('/api/mpesa/callback', $payload)->assertStatus(200);

        $merchant->refresh();
        $this->assertSame(98, $merchant->wallet->fresh()->available_balance);
    }
}
