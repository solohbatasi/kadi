<?php

namespace App\Services\Operations;

use App\Models\MerchantWebhookDelivery;
use App\Models\Payout;
use App\Models\Transaction;
use App\Support\Mask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OperationalAlertService
{
    public function check(): array
    {
        $alerts = collect([
            $this->thresholdAlert('failed_webhook_deliveries', MerchantWebhookDelivery::where('status', 'failed')->count(), config('operations.failed_webhook_threshold', 10)),
            $this->thresholdAlert('pending_payouts', Payout::whereIn('status', ['pending', 'processing'])->count(), config('operations.pending_payout_threshold', 10)),
            $this->thresholdAlert('failed_payouts', Payout::where('status', 'failed')->count(), config('operations.failed_payout_threshold', 10)),
            $this->thresholdAlert('stale_pending_transactions', Transaction::where('status', 'pending')->where('created_at', '<=', now()->subMinutes(config('payments.pending_transaction_timeout_minutes', 15)))->count(), config('operations.stale_transaction_threshold', 20)),
            $this->failedJobsAlert(),
            $this->securityAlert(),
        ])->filter()->values()->map(fn ($alert) => Mask::arraySensitive($alert))->all();

        foreach ($alerts as $alert) {
            Log::warning('Payment operations alert', $alert);
        }

        if ($alerts !== [] && config('operations.alert_email')) {
            Mail::raw($this->emailBody($alerts), function ($message): void {
                $message->to(config('operations.alert_email'))
                    ->subject('Payment operations alert');
            });
        }

        return $alerts;
    }

    protected function thresholdAlert(string $name, int $count, int $threshold): ?array
    {
        if ($count < $threshold) {
            return null;
        }

        return [
            'type' => $name,
            'count' => $count,
            'threshold' => $threshold,
            'severity' => 'warning',
        ];
    }

    protected function failedJobsAlert(): ?array
    {
        if (! DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            return null;
        }

        $count = DB::table('failed_jobs')->count();

        return $count > 0 ? [
            'type' => 'failed_jobs',
            'count' => $count,
            'severity' => 'warning',
        ] : null;
    }

    protected function securityAlert(): ?array
    {
        if (! app()->environment('production')) {
            return null;
        }

        $failures = [];

        if (config('app.debug')) {
            $failures[] = 'APP_DEBUG is true';
        }

        if (config('queue.default') === 'sync') {
            $failures[] = 'QUEUE_CONNECTION is sync';
        }

        if (! config('mpesa.callback_secret')) {
            $failures[] = 'MPESA_CALLBACK_SECRET missing';
        }

        return $failures !== [] ? [
            'type' => 'security_check',
            'severity' => 'critical',
            'failures' => $failures,
        ] : null;
    }

    protected function emailBody(array $alerts): string
    {
        return "Payment operations alerts:\n\n".json_encode($alerts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

