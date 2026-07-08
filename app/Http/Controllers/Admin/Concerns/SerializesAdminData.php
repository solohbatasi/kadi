<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Merchant;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use App\Support\Mask;
use App\Support\PhoneNumber;

trait SerializesAdminData
{
    protected function merchantSummary(?Merchant $merchant): ?array
    {
        if (! $merchant) {
            return null;
        }

        return [
            'id' => $merchant->id,
            'public_id' => $merchant->public_id,
            'business_name' => $merchant->business_name,
            'business_email' => $merchant->business_email,
            'business_phone' => PhoneNumber::mask($merchant->business_phone),
            'status' => $merchant->status,
            'compliance_status' => $merchant->compliance_status,
            'live_enabled' => $merchant->live_enabled,
            'live_requested_at' => $merchant->live_requested_at,
            'live_reviewed_at' => $merchant->live_reviewed_at,
            'live_rejection_reason' => $merchant->live_rejection_reason,
        ];
    }

    protected function transactionSummary(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'public_id' => $transaction->public_id,
            'merchant' => $this->merchantSummary($transaction->merchant),
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'environment' => $transaction->environment,
            'phone' => PhoneNumber::mask($transaction->phone),
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'commission_amount' => $transaction->commission_amount,
            'net_amount' => $transaction->net_amount,
            'status' => $transaction->status,
            'reference' => $transaction->reference,
            'mpesa_receipt_number' => $transaction->mpesa_receipt_number,
            'mpesa_checkout_request_id' => $transaction->mpesa_checkout_request_id,
            'mpesa_merchant_request_id' => $transaction->mpesa_merchant_request_id,
            'metadata' => Mask::arraySensitive($transaction->metadata ?? []),
            'created_at' => $transaction->created_at,
            'paid_at' => $transaction->paid_at,
            'failed_at' => $transaction->failed_at,
        ];
    }

    protected function walletSummary(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'public_id' => $wallet->public_id,
            'merchant' => $this->merchantSummary($wallet->merchant),
            'available_balance' => $wallet->available_balance,
            'pending_balance' => $wallet->pending_balance,
            'currency' => $wallet->currency,
            'created_at' => $wallet->created_at,
        ];
    }

    protected function ledgerSummary(WalletLedgerEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'public_id' => $entry->public_id,
            'entry_type' => $entry->entry_type,
            'direction' => $entry->direction,
            'amount' => $entry->amount,
            'balance_after' => $entry->balance_after,
            'description' => $entry->description,
            'transaction_public_id' => $entry->transaction?->public_id,
            'metadata' => Mask::arraySensitive($entry->metadata ?? []),
            'created_at' => $entry->created_at,
        ];
    }

    protected function payoutSummary(Payout $payout): array
    {
        return [
            'id' => $payout->id,
            'public_id' => $payout->public_id,
            'merchant' => $this->merchantSummary($payout->merchant),
            'recipient' => $payout->recipient ? [
                'public_id' => $payout->recipient->public_id,
                'name' => $payout->recipient->name,
                'phone' => PhoneNumber::mask($payout->recipient->phone),
                'status' => $payout->recipient->status,
            ] : null,
            'transaction_public_id' => $payout->transaction?->public_id,
            'amount' => $payout->amount,
            'currency' => $payout->currency,
            'fee' => $payout->fee,
            'net_amount' => $payout->net_amount,
            'phone' => PhoneNumber::mask($payout->phone),
            'status' => $payout->status,
            'provider' => $payout->provider,
            'provider_conversation_id' => $payout->provider_conversation_id,
            'provider_originator_conversation_id' => $payout->provider_originator_conversation_id,
            'provider_result_code' => $payout->provider_result_code,
            'provider_result_description' => $payout->provider_result_description,
            'failure_reason' => $payout->failure_reason,
            'metadata' => Mask::arraySensitive($payout->metadata ?? []),
            'created_at' => $payout->created_at,
            'paid_at' => $payout->paid_at,
            'failed_at' => $payout->failed_at,
            'reversed_at' => $payout->reversed_at,
        ];
    }
}
