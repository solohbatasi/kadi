<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Services\Payments\MerchantWebhookService;
use Illuminate\Console\Command;

class ExpirePendingTransactions extends Command
{
    protected $signature = 'payments:expire-pending-transactions {--minutes= : Override timeout window in minutes}';

    protected $description = 'Mark stale pending payment transactions as timeout without crediting wallets.';

    public function handle(MerchantWebhookService $webhooks): int
    {
        $minutes = (int) ($this->option('minutes') ?: config('payments.pending_transaction_timeout_minutes', 15));
        $cutoff = now()->subMinutes($minutes);
        $count = 0;

        Transaction::where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use (&$count, $webhooks, $minutes): void {
                foreach ($transactions as $transaction) {
                    $fresh = Transaction::whereKey($transaction->id)->where('status', 'pending')->first();

                    if (! $fresh) {
                        continue;
                    }

                    $fresh->update([
                        'status' => 'timeout',
                        'failed_at' => now(),
                        'mpesa_result_description' => $fresh->mpesa_result_description ?: 'Expired locally after pending timeout window.',
                        'metadata' => array_merge($fresh->metadata ?? [], [
                            'timed_out_by' => 'payments:expire-pending-transactions',
                            'timeout_window_minutes' => $minutes,
                        ]),
                    ]);

                    AuditLog::create([
                        'merchant_id' => $fresh->merchant_id,
                        'action' => 'transaction.timeout_expired',
                        'subject_type' => Transaction::class,
                        'subject_id' => $fresh->id,
                        'metadata' => [
                            'transaction_public_id' => $fresh->public_id,
                            'timeout_window_minutes' => $minutes,
                        ],
                        'created_at' => now(),
                    ]);

                    $webhooks->dispatchTransactionEvent($fresh->fresh(), 'transaction.timeout');
                    $count++;
                }
            });

        $this->info("Expired {$count} pending transaction(s).");

        return self::SUCCESS;
    }
}

