<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SerializesAdminData;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Merchant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantController extends Controller
{
    use SerializesAdminData;

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'compliance_status', 'live_enabled', 'search']);

        $merchants = Merchant::query()
            ->with('wallet')
            ->withCount(['apiKeys', 'paymentLinks', 'invoices', 'payouts'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['compliance_status'] ?? null, fn ($query, $status) => $query->where('compliance_status', $status))
            ->when(isset($filters['live_enabled']) && $filters['live_enabled'] !== '', fn ($query) => $query->where('live_enabled', filter_var($filters['live_enabled'], FILTER_VALIDATE_BOOLEAN)))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('business_name', 'like', "%{$search}%")
                ->orWhere('business_email', 'like', "%{$search}%")
                ->orWhere('business_phone', 'like', "%{$search}%")
                ->orWhere('public_id', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Merchants/Index', [
            'merchants' => $merchants->through(fn (Merchant $merchant) => array_merge($this->merchantSummary($merchant), [
                'wallet' => $merchant->wallet ? [
                    'available_balance' => $merchant->wallet->available_balance,
                    'pending_balance' => $merchant->wallet->pending_balance,
                    'currency' => $merchant->wallet->currency,
                ] : null,
                'api_keys_count' => $merchant->api_keys_count,
                'payment_links_count' => $merchant->payment_links_count,
                'invoices_count' => $merchant->invoices_count,
                'payouts_count' => $merchant->payouts_count,
            ])),
            'filters' => $filters,
        ]);
    }

    public function show(Merchant $merchant): Response
    {
        $merchant->load(['profile', 'wallet', 'webhookEndpoint'])
            ->loadCount(['apiKeys', 'paymentLinks', 'invoices', 'payouts']);

        return Inertia::render('Admin/Merchants/Show', [
            'merchant' => array_merge($this->merchantSummary($merchant), [
                'profile' => $merchant->profile,
                'wallet' => $merchant->wallet ? $this->walletSummary($merchant->wallet) : null,
                'api_keys_count' => $merchant->api_keys_count,
                'payment_links_count' => $merchant->payment_links_count,
                'invoices_count' => $merchant->invoices_count,
                'payouts_count' => $merchant->payouts_count,
                'webhook' => $merchant->webhookEndpoint ? [
                    'url' => $merchant->webhookEndpoint->url,
                    'is_enabled' => $merchant->webhookEndpoint->is_enabled,
                ] : null,
            ]),
            'recentTransactions' => $merchant->transactions()->with('merchant')->latest()->limit(10)->get()->map(fn ($transaction) => $this->transactionSummary($transaction)),
            'recentPayouts' => $merchant->payouts()->with(['merchant', 'recipient', 'transaction'])->latest()->limit(10)->get()->map(fn ($payout) => $this->payoutSummary($payout)),
        ]);
    }

    public function activate(Merchant $merchant): RedirectResponse
    {
        return $this->updateMerchant($merchant, ['status' => 'active'], 'merchant.activated');
    }

    public function suspend(Merchant $merchant): RedirectResponse
    {
        return $this->updateMerchant($merchant, ['status' => 'suspended'], 'merchant.suspended');
    }

    public function enableLive(Merchant $merchant): RedirectResponse
    {
        return $this->updateMerchant($merchant, ['live_enabled' => true], 'merchant.live_enabled');
    }

    public function disableLive(Merchant $merchant): RedirectResponse
    {
        return $this->updateMerchant($merchant, ['live_enabled' => false], 'merchant.live_disabled');
    }

    public function verifyCompliance(Merchant $merchant): RedirectResponse
    {
        return $this->updateMerchant($merchant, ['compliance_status' => 'verified'], 'merchant.compliance_verified');
    }

    public function rejectCompliance(Merchant $merchant): RedirectResponse
    {
        return $this->updateMerchant($merchant, ['compliance_status' => 'rejected'], 'merchant.compliance_rejected');
    }

    protected function updateMerchant(Merchant $merchant, array $attributes, string $action): RedirectResponse
    {
        $merchant->update($attributes);
        $this->audit($merchant, $action, $attributes);

        return back()->with('flash.banner', 'Merchant updated.');
    }

    protected function audit(Merchant $merchant, string $action, array $changes): void
    {
        AuditLog::create([
            'merchant_id' => $merchant->id,
            'user_id' => request()->user()?->id,
            'action' => $action,
            'subject_type' => Merchant::class,
            'subject_id' => $merchant->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'merchant_public_id' => $merchant->public_id,
                'changes' => $changes,
            ],
            'created_at' => now(),
        ]);
    }
}

