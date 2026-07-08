<?php

namespace App\Services\Payments;

use App\Jobs\ProcessPayout;
use App\Models\AuditLog;
use App\Models\Merchant;
use App\Models\Payout;
use App\Models\PayoutRecipient;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Money;
use App\Support\Mask;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PayoutService
{
    public function __construct(
        protected WalletLedgerService $ledger,
        protected MerchantWebhookService $webhooks
    ) {
    }

    public function requestToPhone(Merchant $merchant, string $phone, int $amount, array $metadata = []): Payout
    {
        return $this->request($merchant, null, PhoneNumber::normalize($phone), $amount, $metadata);
    }

    public function requestToRecipient(Merchant $merchant, PayoutRecipient $recipient, int $amount, array $metadata = []): Payout
    {
        if ($recipient->merchant_id !== $merchant->id || $recipient->status !== 'active') {
            throw new InvalidArgumentException('Invalid payout recipient.');
        }

        return $this->request($merchant, $recipient, $recipient->phone, $amount, $metadata);
    }

    public function markProcessing(Payout $payout, array $providerData = []): Payout
    {
        if ($payout->status !== 'pending') {
            return $payout->fresh();
        }

        $payout->update($this->providerColumns($providerData) + [
            'status' => 'processing',
            'provider' => 'mpesa',
            'processed_at' => now(),
        ]);

        $this->webhooks->dispatchPayoutEvent($payout->fresh(), 'payout.processing');

        return $payout->fresh();
    }

    public function markSuccess(Payout $payout, array $providerData = []): Payout
    {
        if ($payout->status === 'success') {
            return $payout->fresh();
        }

        if (in_array($payout->status, ['failed', 'reversed', 'cancelled'], true)) {
            return $payout->fresh();
        }

        $payout->update($this->providerColumns($providerData) + [
            'status' => 'success',
            'paid_at' => now(),
            'failure_reason' => null,
        ]);

        $this->webhooks->dispatchPayoutEvent($payout->fresh(), 'payout.success');

        return $payout->fresh();
    }

    public function markFailed(Payout $payout, string $reason, array $providerData = []): Payout
    {
        if (in_array($payout->status, ['failed', 'reversed'], true)) {
            $this->reverseFailedPayout($payout);
            return $payout->fresh();
        }

        if ($payout->status === 'success') {
            return $payout->fresh();
        }

        $payout->update($this->providerColumns($providerData) + [
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);

        $this->webhooks->dispatchPayoutEvent($payout->fresh(), 'payout.failed');
        $this->reverseFailedPayout($payout->fresh());

        return $payout->fresh();
    }

    public function reverseFailedPayout(Payout $payout): void
    {
        if ($payout->reversed_at !== null) {
            return;
        }

        DB::transaction(function () use ($payout) {
            $payout = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($payout->reversed_at !== null) {
                return;
            }

            $wallet = Wallet::where('merchant_id', $payout->merchant_id)->lockForUpdate()->firstOrFail();
            $this->ledger->credit(
                $wallet,
                $payout->amount,
                'Payout reversal',
                $payout->transaction,
                ['payout_public_id' => $payout->public_id],
                'payout_reversal'
            );

            $payout->update([
                'status' => 'reversed',
                'reversed_at' => now(),
            ]);
        });

        $this->webhooks->dispatchPayoutEvent($payout->fresh(), 'payout.reversed');
    }

    protected function request(Merchant $merchant, ?PayoutRecipient $recipient, string $phone, int $amount, array $metadata): Payout
    {
        $this->validateMerchant($merchant);
        $amount = Money::toInteger($amount);

        if ($amount < (int) config('payments.min_payout_amount', 10)) {
            throw new InvalidArgumentException('Payout amount is below the minimum.');
        }

        return DB::transaction(function () use ($merchant, $recipient, $phone, $amount, $metadata) {
            $wallet = Wallet::where('merchant_id', $merchant->id)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $transaction = Transaction::create([
                'merchant_id' => $merchant->id,
                'public_id' => 'txn_'.bin2hex(random_bytes(16)),
                'type' => 'payout',
                'direction' => 'debit',
                'environment' => config('mpesa.environment', 'sandbox'),
                'phone' => $phone,
                'amount' => $amount,
                'currency' => config('payments.currency', 'KES'),
                'commission_amount' => 0,
                'provider_fee' => 0,
                'net_amount' => $amount,
                'status' => 'pending',
                'reference' => 'Payout',
                'description' => 'Merchant payout',
                'metadata' => $metadata,
            ]);

            $payout = Payout::create([
                'merchant_id' => $merchant->id,
                'payout_recipient_id' => $recipient?->id,
                'transaction_id' => $transaction->id,
                'public_id' => $this->generatePublicId(),
                'amount' => $amount,
                'currency' => config('payments.currency', 'KES'),
                'fee' => 0,
                'net_amount' => $amount,
                'phone' => $phone,
                'status' => 'pending',
                'provider' => 'mpesa',
                'metadata' => $metadata,
                'requested_at' => now(),
            ]);

            $this->ledger->debit(
                $wallet,
                $amount,
                'Payout request',
                $transaction,
                ['payout_public_id' => $payout->public_id],
                'payout_debit'
            );

            $this->audit($payout, 'payout.requested');
            $this->webhooks->dispatchPayoutEvent($payout, 'payout.pending');
            ProcessPayout::dispatch($payout->id)->afterCommit();

            return $payout->fresh(['recipient', 'transaction']);
        });
    }

    protected function validateMerchant(Merchant $merchant): void
    {
        if ($merchant->status !== 'active') {
            throw new InvalidArgumentException('Merchant account is not active.');
        }

        $production = config('mpesa.environment') === 'production';

        if ($production && $merchant->compliance_status !== 'verified') {
            throw new InvalidArgumentException('Verified compliance is required for live payouts.');
        }

        if (! $production
            && $merchant->compliance_status !== 'verified'
            && ! config('payments.allow_sandbox_payouts_without_verified_compliance', true)) {
            throw new InvalidArgumentException('Verified compliance is required for payouts.');
        }
    }

    protected function providerColumns(array $providerData): array
    {
        return array_filter([
            'provider_conversation_id' => $providerData['conversation_id'] ?? $providerData['ConversationID'] ?? null,
            'provider_originator_conversation_id' => $providerData['originator_conversation_id'] ?? $providerData['OriginatorConversationID'] ?? null,
            'provider_result_code' => isset($providerData['result_code']) ? (string) $providerData['result_code'] : ($providerData['ResultCode'] ?? null),
            'provider_result_description' => $providerData['result_description'] ?? $providerData['ResultDesc'] ?? null,
            'metadata' => Mask::arraySensitive(array_merge($providerData['metadata'] ?? [], ['provider_payload' => $providerData])),
        ], fn ($value) => $value !== null && $value !== []);
    }

    protected function audit(Payout $payout, string $action): void
    {
        AuditLog::create([
            'merchant_id' => $payout->merchant_id,
            'user_id' => request()->user()?->id,
            'action' => $action,
            'subject_type' => Payout::class,
            'subject_id' => $payout->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => ['payout_public_id' => $payout->public_id],
            'created_at' => now(),
        ]);
    }

    protected function generatePublicId(): string
    {
        do {
            $publicId = 'po_'.Str::random(24);
        } while (Payout::where('public_id', $publicId)->exists());

        return $publicId;
    }
}
