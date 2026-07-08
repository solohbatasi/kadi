<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\Mpesa\B2CPayoutService;
use App\Services\Payments\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessPayout implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $payoutId)
    {
        $this->onQueue('payouts');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(B2CPayoutService $provider, PayoutService $payouts): void
    {
        $payout = Payout::find($this->payoutId);

        if (! $payout || $payout->status !== 'pending') {
            return;
        }

        try {
            $payout = $payouts->markProcessing($payout);
            $response = $provider->send($payout);

            if ($response['fake'] ?? false) {
                $payouts->markSuccess($payout->fresh(), $response);
                return;
            }

            $payouts->markProcessing($payout->fresh(), $response);
        } catch (Throwable $exception) {
            $payouts->markFailed($payout->fresh(), $exception->getMessage());
        }
    }
}
