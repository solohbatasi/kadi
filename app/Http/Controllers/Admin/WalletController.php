<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SerializesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    use SerializesAdminData;

    public function index(Request $request): Response
    {
        $filters = $request->only(['merchant']);

        $wallets = Wallet::query()
            ->with('merchant')
            ->when($filters['merchant'] ?? null, fn ($query, $merchant) => $query->whereHas('merchant', fn ($inner) => $inner
                ->where('public_id', $merchant)
                ->orWhere('business_name', 'like', "%{$merchant}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Wallets/Index', [
            'wallets' => $wallets->through(fn (Wallet $wallet) => $this->walletSummary($wallet)),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Wallet $wallet): Response
    {
        $filters = $request->only(['entry_type', 'direction', 'date_from', 'date_to']);
        $wallet->load('merchant');

        $entries = WalletLedgerEntry::query()
            ->with('transaction')
            ->where('wallet_id', $wallet->id)
            ->when($filters['entry_type'] ?? null, fn ($query, $type) => $query->where('entry_type', $type))
            ->when($filters['direction'] ?? null, fn ($query, $direction) => $query->where('direction', $direction))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Wallets/Show', [
            'wallet' => $this->walletSummary($wallet),
            'ledgerEntries' => $entries->through(fn (WalletLedgerEntry $entry) => $this->ledgerSummary($entry)),
            'filters' => $filters,
        ]);
    }
}

