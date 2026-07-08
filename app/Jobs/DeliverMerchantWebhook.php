<?php

namespace App\Jobs;

use App\Models\MerchantWebhookDelivery;
use App\Services\Payments\MerchantWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class DeliverMerchantWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $deliveryId)
    {
        $this->onQueue('webhooks');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MerchantWebhookService $webhooks): void
    {
        $delivery = MerchantWebhookDelivery::find($this->deliveryId);

        if (! $delivery || $delivery->status === 'success') {
            return;
        }

        if (! $webhooks->deliver($delivery)) {
            throw new RuntimeException('Merchant webhook delivery failed.');
        }
    }
}
