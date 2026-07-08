<?php

namespace App\Services\Payments;

use App\Jobs\DeliverMerchantWebhook;
use App\Models\Merchant;
use App\Models\MerchantWebhookDelivery;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class MerchantWebhookService
{
    public const EVENTS = [
        'transaction.pending',
        'transaction.success',
        'transaction.failed',
        'transaction.cancelled',
        'transaction.timeout',
        'payout.pending',
        'payout.processing',
        'payout.success',
        'payout.failed',
        'payout.reversed',
    ];

    public function dispatchTransactionEvent(Transaction $transaction, ?string $event = null): ?MerchantWebhookDelivery
    {
        $transaction->loadMissing('merchant.webhookEndpoint');

        $event ??= $this->eventForStatus($transaction->status);

        if (! $event) {
            return null;
        }

        return $this->queueDelivery(
            $transaction->merchant,
            $event,
            $this->payloadForTransaction($transaction, $event),
            $transaction
        );
    }

    public function dispatchTestEvent(Merchant $merchant): ?MerchantWebhookDelivery
    {
        return $this->queueDelivery($merchant, 'transaction.pending', [
            'event' => 'transaction.pending',
            'transaction' => [
                'id' => 'txn_test_webhook',
                'amount' => 100,
                'currency' => config('payments.currency', 'KES'),
                'status' => 'pending',
                'phone' => '2547****678',
                'mpesa_receipt' => null,
                'reference' => 'WEBHOOK-TEST',
                'metadata' => [
                    'test' => true,
                ],
            ],
        ]);
    }

    public function dispatchPayoutEvent(Payout $payout, ?string $event = null): ?MerchantWebhookDelivery
    {
        $payout->loadMissing('merchant.webhookEndpoint', 'recipient');
        $event ??= $this->eventForPayoutStatus($payout->status);

        if (! $event) {
            return null;
        }

        return $this->queueDelivery($payout->merchant, $event, $this->payloadForPayout($payout, $event));
    }

    public function deliver(MerchantWebhookDelivery $delivery): bool
    {
        $delivery->loadMissing('merchant.webhookEndpoint');

        $endpoint = $delivery->merchant->webhookEndpoint;

        if (! $endpoint?->secret) {
            $delivery->update([
                'status' => 'failed',
                'attempts' => $delivery->attempts + 1,
                'error_message' => 'Webhook endpoint secret is not configured.',
            ]);

            return false;
        }

        $timestamp = now()->timestamp;
        $payload = $delivery->payload ?? [];
        $rawPayload = $this->encodePayload($payload);
        $startedAt = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-PayGate-Signature' => $this->signRawPayload($rawPayload, $endpoint->secret, $timestamp),
                    'X-PayGate-Event' => $delivery->event,
                    'X-PayGate-Timestamp' => (string) $timestamp,
                ])
                ->withBody($rawPayload, 'application/json')
                ->post($delivery->url);

            $responseTime = (int) round((microtime(true) - $startedAt) * 1000);
            $successful = $response->successful();

            $delivery->update([
                'status' => $successful ? 'success' : 'failed',
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'attempts' => $delivery->attempts + 1,
                'error_message' => $successful ? null : str($response->body())->limit(1000)->toString(),
                'delivered_at' => $successful ? now() : null,
            ]);

            return $successful;
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'attempts' => $delivery->attempts + 1,
                'error_message' => str($exception->getMessage())->limit(1000)->toString(),
            ]);

            return false;
        }
    }

    public function signPayload(array $payload, string $secret, int $timestamp): string
    {
        return $this->signRawPayload($this->encodePayload($payload), $secret, $timestamp);
    }

    public function signRawPayload(string $rawPayload, string $secret, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$rawPayload, $secret);
    }

    public function eventForStatus(string $status): ?string
    {
        return match ($status) {
            'pending' => 'transaction.pending',
            'success' => 'transaction.success',
            'failed' => 'transaction.failed',
            'cancelled' => 'transaction.cancelled',
            'timeout' => 'transaction.timeout',
            default => null,
        };
    }

    public function eventForPayoutStatus(string $status): ?string
    {
        return match ($status) {
            'pending' => 'payout.pending',
            'processing' => 'payout.processing',
            'success' => 'payout.success',
            'failed' => 'payout.failed',
            'reversed' => 'payout.reversed',
            default => null,
        };
    }

    protected function queueDelivery(
        Merchant $merchant,
        string $event,
        array $payload,
        ?Transaction $transaction = null
    ): ?MerchantWebhookDelivery {
        $merchant->loadMissing('webhookEndpoint');
        $endpoint = $merchant->webhookEndpoint;

        if (! $endpoint?->is_enabled || ! $endpoint->url || ! $endpoint->secret) {
            return null;
        }

        $delivery = MerchantWebhookDelivery::create([
            'merchant_id' => $merchant->id,
            'transaction_id' => $transaction?->id,
            'event' => $event,
            'url' => $endpoint->url,
            'status' => 'pending',
            'attempts' => 0,
            'payload' => $payload,
        ]);

        DeliverMerchantWebhook::dispatch($delivery->id)->afterCommit();

        return $delivery;
    }

    protected function payloadForTransaction(Transaction $transaction, string $event): array
    {
        return [
            'event' => $event,
            'transaction' => [
                'id' => $transaction->public_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'phone' => $this->maskPhone($transaction->phone),
                'mpesa_receipt' => $transaction->status === 'success' ? $transaction->mpesa_receipt_number : null,
                'reference' => $transaction->reference,
                'metadata' => $transaction->metadata ?? [],
            ],
        ];
    }

    protected function payloadForPayout(Payout $payout, string $event): array
    {
        return [
            'event' => $event,
            'payout' => [
                'id' => $payout->public_id,
                'amount' => $payout->amount,
                'currency' => $payout->currency,
                'status' => $payout->status,
                'phone' => $this->maskPhone($payout->phone),
                'recipient_id' => $payout->recipient?->public_id,
                'failure_reason' => $payout->failure_reason,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    protected function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        if (strlen($phone) <= 7) {
            return str_repeat('*', strlen($phone));
        }

        return substr($phone, 0, 4).'****'.substr($phone, -3);
    }

    protected function encodePayload(array $payload): string
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new RuntimeException('Unable to encode webhook payload.');
        }

        return $encoded;
    }
}
