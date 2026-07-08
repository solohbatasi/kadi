<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $merchant = $request->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $transactions = $merchant->transactions()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'public_id' => $transaction->public_id,
                'type' => $transaction->type,
                'direction' => $transaction->direction,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'reference' => $transaction->reference,
                'receipt' => $transaction->mpesa_receipt_number,
                'created_at' => $transaction->created_at,
            ]);

        return Inertia::render('Developer/Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }
}

