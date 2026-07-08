<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\WalletLedgerEntry;
use Illuminate\Console\Command;

class ReconciliationReport extends Command
{
    protected $signature = 'payments:reconciliation-report {--csv= : Optional CSV output path}';

    protected $description = 'Generate a read-only payment reconciliation summary.';

    public function handle(): int
    {
        $rows = $this->rows();

        $this->table(['Metric', 'Amount'], $rows);

        if ($path = $this->option('csv')) {
            $csv = "metric,amount\n".collect($rows)
                ->map(fn ($row) => '"'.str_replace('"', '""', $row[0]).'",'.$row[1])
                ->implode("\n")."\n";
            file_put_contents($path, $csv);
            $this->info("CSV report written to {$path}");
        }

        return self::SUCCESS;
    }

    protected function rows(): array
    {
        $successful = Transaction::where('status', 'success');
        $gross = (clone $successful)->sum('amount');
        $commission = (clone $successful)->sum('commission_amount');
        $net = (clone $successful)->sum('net_amount');
        $ledgerCredits = WalletLedgerEntry::where('entry_type', 'payment_credit')->where('direction', 'credit')->sum('amount');
        $payoutDebits = WalletLedgerEntry::where('entry_type', 'payout_debit')->where('direction', 'debit')->sum('amount');
        $payoutReversals = WalletLedgerEntry::where('entry_type', 'payout_reversal')->where('direction', 'credit')->sum('amount');

        return [
            ['successful_transaction_gross_volume', $gross],
            ['total_commission', $commission],
            ['total_net_credited', $net],
            ['wallet_ledger_credit_total', $ledgerCredits],
            ['payout_debit_total', $payoutDebits],
            ['payout_reversal_total', $payoutReversals],
            ['net_vs_ledger_credit_difference', $net - $ledgerCredits],
            ['payout_net_difference', $payoutDebits - $payoutReversals],
        ];
    }
}

