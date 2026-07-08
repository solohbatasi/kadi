<?php

namespace App\Services\Payments;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Support\Facades\DB;

class WalletLedgerService
{
    public function credit(Wallet $wallet, int $amount, string $description, ?Transaction $transaction = null, array $metadata = []): WalletLedgerEntry
    {
        return $this->adjustBalance($wallet, 'credit', $amount, $description, $transaction, $metadata);
    }

    public function debit(Wallet $wallet, int $amount, string $description, ?Transaction $transaction = null, array $metadata = []): WalletLedgerEntry
    {
        return $this->adjustBalance($wallet, 'debit', $amount, $description, $transaction, $metadata);
    }

    protected function adjustBalance(Wallet $wallet, string $direction, int $amount, string $description, ?Transaction $transaction, array $metadata): WalletLedgerEntry
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($wallet, $direction, $amount, $description, $transaction, $metadata) {
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
                'entry_type' => $transaction ? 'transaction' : 'adjustment',
                'direction' => $direction,
                'amount' => $amount,
                'balance_after' => $adjustedBalance,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }
}
