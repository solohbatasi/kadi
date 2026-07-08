<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function show(Request $request): Response
    {
        $merchant = $request->user()->merchant;

        if (! $merchant || ! $merchant->wallet) {
            abort(404, 'Merchant wallet not found.');
        }

        return Inertia::render('Developer/Wallet/Show', [
            'wallet' => [
                'available_balance' => $merchant->wallet->available_balance,
                'pending_balance' => $merchant->wallet->pending_balance,
                'currency' => $merchant->wallet->currency,
                'public_id' => $merchant->wallet->public_id ?? null,
            ],
        ]);
    }
}
