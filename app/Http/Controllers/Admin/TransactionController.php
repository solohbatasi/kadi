<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SerializesAdminData;
use App\Http\Controllers\Controller;
use App\Models\MerchantWebhookDelivery;
use App\Models\MpesaCallback;
use App\Models\Transaction;
use App\Models\WalletLedgerEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    use SerializesAdminData;

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'type', 'environment', 'merchant', 'date_from', 'date_to', 'search']);

        $transactions = Transaction::query()
            ->with('merchant')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['environment'] ?? null, fn ($query, $environment) => $query->where('environment', $environment))
            ->when($filters['merchant'] ?? null, fn ($query, $merchant) => $query->whereHas('merchant', fn ($inner) => $inner
                ->where('public_id', $merchant)
                ->orWhere('business_name', 'like', "%{$merchant}%")))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('public_id', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhere('mpesa_receipt_number', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions->through(fn (Transaction $transaction) => $this->transactionSummary($transaction)),
            'filters' => $filters,
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->load('merchant');

        return Inertia::render('Admin/Transactions/Show', [
            'transaction' => $this->transactionSummary($transaction),
            'callbacks' => MpesaCallback::where('transaction_id', $transaction->id)->latest()->get()->map(fn ($callback) => [
                'id' => $callback->id,
                'checkout_request_id' => $callback->checkout_request_id,
                'merchant_request_id' => $callback->merchant_request_id,
                'result_code' => $callback->result_code,
                'result_description' => $callback->result_description,
                'raw_payload' => $callback->raw_payload,
                'processed_at' => $callback->processed_at,
            ]),
            'ledgerEntries' => WalletLedgerEntry::with('transaction')->where('transaction_id', $transaction->id)->latest()->get()->map(fn ($entry) => $this->ledgerSummary($entry)),
            'webhookDeliveries' => MerchantWebhookDelivery::where('transaction_id', $transaction->id)->latest()->get()->map(fn ($delivery) => [
                'id' => $delivery->id,
                'event' => $delivery->event,
                'url' => $delivery->url,
                'status' => $delivery->status,
                'status_code' => $delivery->status_code,
                'response_time_ms' => $delivery->response_time_ms,
                'attempts' => $delivery->attempts,
                'error_message' => $delivery->error_message,
                'created_at' => $delivery->created_at,
            ]),
        ]);
    }
}

