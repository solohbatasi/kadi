<?php

namespace App\Services\Payments;

use App\Models\Merchant;
use App\Models\MpesaCallback;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransactionService
{
    public function createPendingStkPush(
        Merchant $merchant,
        string $phone,
        int $amount,
        string $currency,
        int $commissionAmount,
        int $netAmount,
        string $reference,
        string $description,
        string $idempotencyKey,
        string $environment
    ): Transaction {
        $existing = Transaction::where('merchant_id', $merchant->id)
            ->where('idempotency_key', $idempotencyKey)
            ->where('type', 'stk_push')
            ->first();

        if ($existing) {
            if ($existing->environment !== $environment) {
                throw new RuntimeException('Idempotency key already used with different environment.');
            }

            return $existing;
        }

        return Transaction::create([
            'merchant_id' => $merchant->id,
            'public_id' => 'txn_'.bin2hex(random_bytes(16)),
            'type' => 'stk_push',
            'direction' => 'credit',
            'environment' => $environment,
            'phone' => $phone,
            'amount' => $amount,
            'currency' => $currency,
            'commission_amount' => $commissionAmount,
            'provider_fee' => 0,
            'net_amount' => $netAmount,
            'status' => 'pending',
            'reference' => $reference,
            'description' => $description,
            'idempotency_key' => $idempotencyKey,
            'metadata' => ['initiated_by' => 'api'],
        ]);
    }

    public function processStkCallback(array $payload): ?Transaction
    {
        $checkoutRequestId = $payload['checkout_request_id'] ?? null;
        $merchantRequestId = $payload['merchant_request_id'] ?? null;
        $resultCode = $payload['result_code'] ?? null;
        $resultDescription = $payload['result_description'] ?? null;
        $customerMessage = $payload['customer_message'] ?? null;
        $callbackMetadata = $payload['callback_metadata'] ?? [];

        [$transaction, $shouldDispatchWebhook] = DB::transaction(function () use (
            $checkoutRequestId,
            $merchantRequestId,
            $resultCode,
            $resultDescription,
            $customerMessage,
            $callbackMetadata,
            $payload
        ) {
            $transaction = null;

            if ($checkoutRequestId) {
                $transaction = Transaction::where('mpesa_checkout_request_id', $checkoutRequestId)
                    ->lockForUpdate()
                    ->first();
            }

            $callbackData = [
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId,
                'result_code' => $resultCode,
                'result_description' => $resultDescription,
                'raw_payload' => $payload,
                'processed_at' => now(),
            ];

            if (! $transaction) {
                MpesaCallback::create($callbackData);
                return [null, false];
            }

            $transaction->callbacks()->create($callbackData);

            if ($transaction->status !== 'pending') {
                $transaction->update([
                    'mpesa_result_code' => $resultCode,
                    'mpesa_result_description' => $resultDescription,
                    'customer_message' => $customerMessage,
                ]);

                return [$transaction, false];
            }

            if ((string) $resultCode === '0') {
                $wallet = Wallet::where('merchant_id', $transaction->merchant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $ledgerService = app(WalletLedgerService::class);
                $ledgerService->credit(
                    $wallet,
                    $transaction->net_amount,
                    'STK Push settlement',
                    $transaction,
                    ['checkout_request_id' => $checkoutRequestId] + $callbackMetadata
                );

                $transaction->update([
                    'mpesa_result_code' => $resultCode,
                    'mpesa_result_description' => $resultDescription,
                    'customer_message' => $customerMessage,
                    'mpesa_receipt_number' => $callbackMetadata['MpesaReceiptNumber'] ?? $transaction->mpesa_receipt_number,
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                return [$transaction->fresh(), true];
            }

            $status = $this->statusForMpesaResultCode((string) $resultCode);

            $transaction->update([
                'mpesa_result_code' => $resultCode,
                'mpesa_result_description' => $resultDescription,
                'customer_message' => $customerMessage,
                'status' => $status,
                'failed_at' => now(),
            ]);

            return [$transaction->fresh(), true];
        });

        if ($transaction && $shouldDispatchWebhook) {
            app(MerchantWebhookService::class)->dispatchTransactionEvent($transaction);
        }

        return $transaction;
    }

    protected function statusForMpesaResultCode(string $resultCode): string
    {
        return match ($resultCode) {
            '1032' => 'cancelled',
            '1037' => 'timeout',
            default => 'failed',
        };
    }
}
