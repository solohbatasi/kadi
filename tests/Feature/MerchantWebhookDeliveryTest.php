<?php

namespace Tests\Feature;

use App\Jobs\DeliverMerchantWebhook;
use App\Models\ApiKey;
use App\Models\MerchantWebhookDelivery;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payments\MerchantWebhookService;
use App\Services\Payments\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MerchantWebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_logs_successful_attempt_and_signing_headers(): void
    {
        Http::fake([
            'https://merchant.test/webhook' => Http::response(['ok' => true], 200),
        ]);

        $merchant = $this->merchantWithWebhook();
        Carbon::setTestNow('2026-07-08 12:00:00');
        $payload = [
            'event' => 'transaction.success',
            'transaction' => [
                'id' => 'txn_public',
                'amount' => 100,
                'status' => 'success',
                'phone' => '2547****678',
                'mpesa_receipt' => 'RCP123',
                'reference' => 'ORDER-1',
                'metadata' => [],
            ],
        ];
        $delivery = MerchantWebhookDelivery::create([
            'merchant_id' => $merchant->id,
            'event' => 'transaction.success',
            'url' => 'https://merchant.test/webhook',
            'status' => 'pending',
            'payload' => $payload,
        ]);

        $delivered = app(MerchantWebhookService::class)->deliver($delivery);

        $this->assertTrue($delivered);
        $delivery->refresh();
        $this->assertSame('success', $delivery->status);
        $this->assertSame(200, $delivery->status_code);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->response_time_ms);
        $this->assertNull($delivery->error_message);
        $this->assertNotNull($delivery->delivered_at);

        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $expectedSignature = hash_hmac('sha256', Carbon::getTestNow()->timestamp.'.'.$rawPayload, 'webhook-secret-value');

        Http::assertSent(function ($request) use ($rawPayload, $expectedSignature) {
            return $request->body() === $rawPayload
                && $request->hasHeader('X-PayGate-Signature', $expectedSignature)
                && $request->hasHeader('X-PayGate-Event', 'transaction.success')
                && $request->hasHeader('X-PayGate-Timestamp', (string) Carbon::getTestNow()->timestamp)
                && ! isset($request->data()['transaction']['merchant_id'])
                && ! isset($request->data()['transaction']['id_internal']);
        });

        Carbon::setTestNow();
    }

    public function test_failed_delivery_is_logged_and_job_is_retryable(): void
    {
        Http::fake([
            'https://merchant.test/webhook' => Http::response('server error', 500),
        ]);

        $merchant = $this->merchantWithWebhook();
        $delivery = MerchantWebhookDelivery::create([
            'merchant_id' => $merchant->id,
            'event' => 'transaction.failed',
            'url' => 'https://merchant.test/webhook',
            'status' => 'pending',
            'payload' => [
                'event' => 'transaction.failed',
                'transaction' => [
                    'id' => 'txn_public',
                    'amount' => 100,
                    'status' => 'failed',
                    'phone' => '2547****678',
                    'mpesa_receipt' => null,
                    'reference' => 'ORDER-1',
                    'metadata' => [],
                ],
            ],
        ]);

        $job = new DeliverMerchantWebhook($delivery->id);

        $this->assertSame([60, 300, 900], $job->backoff());

        try {
            $job->handle(app(MerchantWebhookService::class));
            $this->fail('Expected failed webhook delivery to throw for queue retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Merchant webhook delivery failed.', $exception->getMessage());
        }

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame(500, $delivery->status_code);
        $this->assertSame(1, $delivery->attempts);
        $this->assertSame('server error', $delivery->error_message);
    }

    public function test_transaction_callback_queues_webhook_delivery_job(): void
    {
        Bus::fake();

        $merchant = $this->merchantWithWebhook();
        Wallet::create([
            'merchant_id' => $merchant->id,
            'available_balance' => 0,
            'pending_balance' => 0,
            'currency' => 'KES',
        ]);
        Transaction::create([
            'merchant_id' => $merchant->id,
            'public_id' => 'txn_public',
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
            'reference' => 'ORDER-1',
            'description' => 'Test',
            'idempotency_key' => 'idem-1',
            'mpesa_checkout_request_id' => 'ws_CO_123',
        ]);

        app(TransactionService::class)->processStkCallback([
            'checkout_request_id' => 'ws_CO_123',
            'merchant_request_id' => '123',
            'result_code' => '0',
            'result_description' => 'Success',
            'customer_message' => 'Success',
            'callback_metadata' => [
                'MpesaReceiptNumber' => 'RCP123',
            ],
        ]);

        $delivery = MerchantWebhookDelivery::first();

        $this->assertNotNull($delivery);
        $this->assertSame('transaction.success', $delivery->event);
        $this->assertSame('txn_public', $delivery->payload['transaction']['id']);
        $this->assertSame('2547****678', $delivery->payload['transaction']['phone']);
        $this->assertSame('RCP123', $delivery->payload['transaction']['mpesa_receipt']);

        Bus::assertDispatched(DeliverMerchantWebhook::class);
    }

    public function test_stk_push_queues_pending_webhook_delivery_job(): void
    {
        Bus::fake();
        config([
            'mpesa.consumer_key' => 'consumer-key',
            'mpesa.consumer_secret' => 'consumer-secret',
            'mpesa.shortcode' => '174379',
            'mpesa.passkey' => 'passkey',
            'mpesa.callback_url' => 'https://paygate.test/api/mpesa/stk-callback/secret',
        ]);
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'daraja-token'], 200),
            'sandbox.safaricom.co.ke/mpesa/stkpush/*' => Http::response([
                'MerchantRequestID' => '12345',
                'CheckoutRequestID' => 'ws_CO_PENDING',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);

        $merchant = $this->merchantWithWebhook();
        ApiKey::create([
            'merchant_id' => $merchant->id,
            'name' => 'Test Key',
            'environment' => 'sandbox',
            'publishable_key' => 'pay_pk_test',
            'secret_key_hash' => bcrypt('pay_sk_test'),
            'secret_key_prefix' => substr('pay_sk_test', 0, 10),
            'secret_key_last_four' => substr('pay_sk_test', -4),
            'status' => 'active',
        ]);

        $this->withHeaders([
            'x-api-key' => 'pay_sk_test',
            'Idempotency-Key' => 'idem-pending',
        ])->postJson('/api/v1/transactions/push-stk', [
            'phone' => '0712345678',
            'amount' => 100,
            'reference' => 'ORDER-PENDING',
            'description' => 'Test payment',
        ])->assertStatus(200);

        $delivery = MerchantWebhookDelivery::first();

        $this->assertNotNull($delivery);
        $this->assertSame('transaction.pending', $delivery->event);
        $this->assertSame('pending', $delivery->payload['transaction']['status']);
        $this->assertSame('2547****678', $delivery->payload['transaction']['phone']);
        $this->assertSame('ORDER-PENDING', $delivery->payload['transaction']['reference']);

        Bus::assertDispatched(DeliverMerchantWebhook::class);
    }

    public function test_transaction_callback_queues_failed_cancelled_and_timeout_events(): void
    {
        Bus::fake();

        foreach ([
            '1' => 'transaction.failed',
            '1032' => 'transaction.cancelled',
            '1037' => 'transaction.timeout',
        ] as $resultCode => $event) {
            $merchant = $this->merchantWithWebhook();
            Transaction::create([
                'merchant_id' => $merchant->id,
                'public_id' => 'txn_'.str()->random(16),
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
                'reference' => 'ORDER-'.$resultCode,
                'description' => 'Test',
                'idempotency_key' => 'idem-'.$resultCode,
                'mpesa_checkout_request_id' => 'ws_CO_'.$resultCode,
            ]);

            app(TransactionService::class)->processStkCallback([
                'checkout_request_id' => 'ws_CO_'.$resultCode,
                'merchant_request_id' => '123',
                'result_code' => $resultCode,
                'result_description' => 'Not successful',
                'customer_message' => 'Not successful',
                'callback_metadata' => [],
            ]);

            $this->assertDatabaseHas('merchant_webhook_deliveries', [
                'merchant_id' => $merchant->id,
                'event' => $event,
                'status' => 'pending',
            ]);
        }

        Bus::assertDispatchedTimes(DeliverMerchantWebhook::class, 3);
    }

    protected function merchantWithWebhook()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_test_'.str()->random(8),
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);
        $merchant->webhookEndpoint()->create([
            'url' => 'https://merchant.test/webhook',
            'secret' => 'webhook-secret-value',
            'is_enabled' => true,
        ]);

        return $merchant;
    }
}
