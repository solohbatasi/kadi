<?php

namespace App\Services\Payments;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Support\Facades\DB;

class WalletLedgerService
{
    public function credit(Wallet $wallet, int $amount, string $description, ?Transaction $transaction = null, array $metadata = [], ?string $entryType = null): WalletLedgerEntry
    {
        return $this->adjustBalance($wallet, 'credit', $amount, $description, $transaction, $metadata, $entryType);
    }

    public function debit(Wallet $wallet, int $amount, string $description, ?Transaction $transaction = null, array $metadata = [], ?string $entryType = null): WalletLedgerEntry
    {
        return $this->adjustBalance($wallet, 'debit', $amount, $description, $transaction, $metadata, $entryType);
    }

    protected function adjustBalance(Wallet $wallet, string $direction, int $amount, string $description, ?Transaction $transaction, array $metadata, ?string $entryType): WalletLedgerEntry
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($wallet, $direction, $amount, $description, $transaction, $metadata, $entryType) {
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($direction === 'debit' && $wallet->available_balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $adjustedBalance = $direction === 'credit'
                ? $wallet->available_balance + $amount
                : $wallet->available_balance - $amount;

            $wallet->available_balance = $adjustedBalance;
            $wallet->save();

            return WalletLedgerEntry::create([
                'wallet_id' => $wallet->id,
                'merchant_id' => $wallet->merchant_id,
                'transaction_id' => $transaction?->id,
                'public_id' => 'wle_'.bin2hex(random_bytes(16)),
                'entry_type' => $entryType ?? ($transaction
                    ? ($direction === 'credit' ? 'payment_credit' : 'commission_debit')
                    : 'manual_adjustment'),
                'direction' => $direction,
                'amount' => $amount,
                'balance_after' => $adjustedBalance,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }
}
