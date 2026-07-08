<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\DeliverMerchantWebhook;
use App\Http\Controllers\Admin\Concerns\SerializesAdminData;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MerchantWebhookDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookDeliveryController extends Controller
{
    use SerializesAdminData;

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'event', 'merchant', 'date_from', 'date_to']);

        $deliveries = MerchantWebhookDelivery::query()
            ->with('merchant')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['merchant'] ?? null, fn ($query, $merchant) => $query->whereHas('merchant', fn ($inner) => $inner
                ->where('public_id', $merchant)
                ->orWhere('business_name', 'like', "%{$merchant}%")))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/WebhookDeliveries/Index', [
            'deliveries' => $deliveries->through(fn (MerchantWebhookDelivery $delivery) => $this->serialize($delivery)),
            'filters' => $filters,
        ]);
    }

    public function show(MerchantWebhookDelivery $delivery): Response
    {
        $delivery->load('merchant');

        return Inertia::render('Admin/WebhookDeliveries/Show', [
            'delivery' => $this->serialize($delivery, true),
        ]);
    }

    public function retry(MerchantWebhookDelivery $delivery): RedirectResponse
    {
        if ($delivery->status !== 'success') {
            $delivery->update(['status' => 'pending', 'error_message' => null]);
            DeliverMerchantWebhook::dispatch($delivery->id);

            AuditLog::create([
                'merchant_id' => $delivery->merchant_id,
                'user_id' => request()->user()?->id,
                'action' => 'webhook_delivery.retry_queued',
                'subject_type' => MerchantWebhookDelivery::class,
                'subject_id' => $delivery->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'event' => $delivery->event,
                    'delivery_id' => $delivery->id,
                ],
                'created_at' => now(),
            ]);
        }

        return back()->with('flash.banner', 'Webhook retry queued.');
    }

    protected function serialize(MerchantWebhookDelivery $delivery, bool $includePayload = false): array
    {
        return [
            'id' => $delivery->id,
            'merchant' => $this->merchantSummary($delivery->merchant),
            'event' => $delivery->event,
            'url' => $delivery->url,
            'status' => $delivery->status,
            'status_code' => $delivery->status_code,
            'response_time_ms' => $delivery->response_time_ms,
            'attempts' => $delivery->attempts,
            'error_message' => $delivery->error_message,
            'payload' => $includePayload ? ($delivery->payload ?? []) : null,
            'delivered_at' => $delivery->delivered_at,
            'created_at' => $delivery->created_at,
        ];
    }
}

