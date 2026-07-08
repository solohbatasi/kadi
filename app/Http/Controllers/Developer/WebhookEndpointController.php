<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\UpdateWebhookEndpointRequest;
use App\Services\Payments\MerchantBootstrapService;
use App\Services\Payments\MerchantWebhookService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WebhookEndpointController extends Controller
{
    public function show(MerchantBootstrapService $bootstrap): Response
    {
        $merchant = auth()->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $endpoint = $bootstrap->ensureWebhookEndpoint($merchant);

        return Inertia::render('Developer/Webhooks/Show', [
            'endpoint' => [
                'url' => $endpoint->url,
                'is_enabled' => $endpoint->is_enabled,
                'has_secret' => filled($endpoint->secret),
            ],
            'deliveries' => $merchant->webhookDeliveries()
                ->latest()
                ->limit(20)
                ->get(['event', 'url', 'status', 'status_code', 'response_time_ms', 'attempts', 'error_message', 'delivered_at', 'created_at']),
        ]);
    }

    public function update(
        UpdateWebhookEndpointRequest $request,
        MerchantBootstrapService $bootstrap
    ): RedirectResponse {
        $merchant = $request->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $endpoint = $bootstrap->ensureWebhookEndpoint($merchant);
        $validated = $request->validated();

        $endpoint->url = $validated['url'] ?? $endpoint->url;
        $endpoint->is_enabled = $validated['is_enabled'];

        if (! empty($validated['secret'])) {
            $endpoint->secret = $validated['secret'];
        }

        $endpoint->save();

        return back()->with('flash.banner', 'Webhook endpoint updated.');
    }

    public function test(MerchantWebhookService $webhooks): RedirectResponse
    {
        $merchant = auth()->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $delivery = $webhooks->dispatchTestEvent($merchant);

        if (! $delivery) {
            return back()->with('flash.banner', 'Enable a webhook endpoint before sending a test event.');
        }

        return back()->with('flash.banner', 'Webhook test event queued.');
    }
}
