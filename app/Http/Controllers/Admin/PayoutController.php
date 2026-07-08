<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SerializesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\WalletLedgerEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    use SerializesAdminData;

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'merchant', 'provider', 'date_from', 'date_to']);

        $payouts = Payout::query()
            ->with(['merchant', 'recipient', 'transaction'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['provider'] ?? null, fn ($query, $provider) => $query->where('provider', $provider))
            ->when($filters['merchant'] ?? null, fn ($query, $merchant) => $query->whereHas('merchant', fn ($inner) => $inner
                ->where('public_id', $merchant)
                ->orWhere('business_name', 'like', "%{$merchant}%")))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $payouts->through(fn (Payout $payout) => $this->payoutSummary($payout)),
            'filters' => $filters,
        ]);
    }

    public function show(Payout $payout): Response
    {
        $payout->load(['merchant', 'recipient', 'transaction']);

        return Inertia::render('Admin/Payouts/Show', [
            'payout' => $this->payoutSummary($payout),
            'ledgerEntries' => WalletLedgerEntry::with('transaction')
                ->where('merchant_id', $payout->merchant_id)
                ->where(fn ($query) => $query
                    ->where('transaction_id', $payout->transaction_id)
                    ->orWhere('metadata->payout_public_id', $payout->public_id))
                ->latest()
                ->get()
                ->map(fn ($entry) => $this->ledgerSummary($entry)),
        ]);
    }
}

