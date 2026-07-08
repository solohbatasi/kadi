<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\StorePayoutRequest;
use App\Services\Payments\PayoutService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    public function __construct(protected PayoutService $payouts)
    {
    }

    public function index(): Response
    {
        $merchant = auth()->user()->merchant()->with('wallet')->first();

        if (! $merchant) {
            abort(404);
        }

        return Inertia::render('Developer/Payouts/Index', [
            'wallet' => [
                'available_balance' => $merchant->wallet?->available_balance ?? 0,
                'currency' => $merchant->wallet?->currency ?? config('payments.currency', 'KES'),
            ],
            'recipients' => $merchant->payoutRecipients()->latest()->get()->map(fn ($recipient) => $this->recipient($recipient)),
            'payouts' => $merchant->payouts()->with('recipient')->latest()->limit(30)->get()->map(fn ($payout) => $this->payout($payout)),
            'minimumAmount' => config('payments.min_payout_amount', 10),
        ]);
    }

    public function store(StorePayoutRequest $request): RedirectResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();
        $recipient = null;

        if ($request->input('recipient_public_id')) {
            $recipient = $merchant->payoutRecipients()
                ->where('public_id', $request->input('recipient_public_id'))
                ->firstOrFail();
            $this->payouts->requestToRecipient($merchant, $recipient, (int) $request->input('amount'));
        } else {
            $this->payouts->requestToPhone($merchant, $request->input('phone'), (int) $request->input('amount'));
        }

        return back()->with('flash.banner', 'Payout requested.');
    }

    protected function recipient($recipient): array
    {
        return [
            'public_id' => $recipient->public_id,
            'name' => $recipient->name,
            'phone' => $recipient->phone,
            'status' => $recipient->status,
        ];
    }

    protected function payout($payout): array
    {
        return [
            'public_id' => $payout->public_id,
            'amount' => $payout->amount,
            'currency' => $payout->currency,
            'status' => $payout->status,
            'phone' => $payout->phone,
            'recipient' => $payout->recipient ? $this->recipient($payout->recipient) : null,
            'failure_reason' => $payout->failure_reason,
            'created_at' => $payout->created_at,
        ];
    }
}
