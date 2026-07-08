<?php

namespace Tests\Unit;

use App\Services\Payments\MerchantWebhookService;
use Tests\TestCase;

class MerchantWebhookServiceTest extends TestCase
{
    public function test_signs_payload_with_hmac_sha256(): void
    {
        $service = new MerchantWebhookService();
        $payload = [
            'event' => 'transaction.success',
            'transaction' => [
                'id' => 'txn_public',
                'amount' => 100,
            ],
        ];
        $timestamp = 1720000000;

        $signature = $service->signPayload($payload, 'secret-value', $timestamp);

        $this->assertSame(
            hash_hmac('sha256', $timestamp.'.'.json_encode($payload, JSON_UNESCAPED_SLASHES), 'secret-value'),
            $signature
        );
    }

    public function test_signs_raw_json_payload_with_timestamp(): void
    {
        $service = new MerchantWebhookService();
        $rawPayload = '{"event":"transaction.success","transaction":{"id":"txn_public","amount":100}}';
        $timestamp = 1720000000;

        $signature = $service->signRawPayload($rawPayload, 'secret-value', $timestamp);

        $this->assertSame(
            hash_hmac('sha256', $timestamp.'.'.$rawPayload, 'secret-value'),
            $signature
        );
    }

    public function test_maps_transaction_status_to_event_name(): void
    {
        $service = new MerchantWebhookService();

        $this->assertSame('transaction.pending', $service->eventForStatus('pending'));
        $this->assertSame('transaction.success', $service->eventForStatus('success'));
        $this->assertSame('transaction.failed', $service->eventForStatus('failed'));
        $this->assertSame('transaction.cancelled', $service->eventForStatus('cancelled'));
        $this->assertSame('transaction.timeout', $service->eventForStatus('timeout'));
    }
}
