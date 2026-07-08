<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SerializesAdminData;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantWebhookDelivery;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use SerializesAdminData;

    public function index(): Response
    {
        $today = Carbon::today();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_merchants' => Merchant::count(),
                'active_merchants' => Merchant::where('status', 'active')->count(),
                'live_enabled_merchants' => Merchant::where('live_enabled', true)->count(),
                'today_successful_volume' => Transaction::where('status', 'success')->whereDate('created_at', $today)->sum('amount'),
                'today_platform_commission' => Transaction::where('status', 'success')->whereDate('created_at', $today)->sum('commission_amount'),
                'pending_payouts' => Payout::whereIn('status', ['pending', 'processing'])->count(),
                'failed_payouts' => Payout::where('status', 'failed')->count(),
                'failed_webhook_deliveries' => MerchantWebhookDelivery::where('status', 'failed')->count(),
            ],
            'recentTransactions' => Transaction::with('merchant')->latest()->limit(8)->get()->map(fn ($transaction) => $this->transactionSummary($transaction)),
            'recentPayouts' => Payout::with(['merchant', 'recipient', 'transaction'])->latest()->limit(8)->get()->map(fn ($payout) => $this->payoutSummary($payout)),
        ]);
    }
}

