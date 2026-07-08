<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePayoutRequest;
use App\Models\Payout;
use App\Services\Payments\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(protected PayoutService $payouts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $payouts = $request->attributes->get('merchant')
            ->payouts()
            ->with('recipient')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->success('Payouts retrieved.', [
            'items' => collect($payouts->items())->map(fn (Payout $payout) => $this->serialize($payout))->values(),
            'pagination' => [
                'current_page' => $payouts->currentPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
                'last_page' => $payouts->lastPage(),
            ],
        ]);
    }

    public function store(StorePayoutRequest $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchant');

        if ($request->input('recipient_public_id')) {
            $recipient = $merchant->payoutRecipients()->where('public_id', $request->input('recipient_public_id'))->firstOrFail();
            $payout = $this->payouts->requestToRecipient($merchant, $recipient, (int) $request->input('amount'));
        } else {
            $payout = $this->payouts->requestToPhone($merchant, $request->input('phone'), (int) $request->input('amount'));
        }

        return $this->success('Payout requested.', $this->serialize($payout), 201);
    }

    public function show(Request $request, string $publicId): JsonResponse
    {
        $payout = $request->attributes->get('merchant')->payouts()->with('recipient')->where('public_id', $publicId)->first();

        if (! $payout) {
            return $this->error('Payout not found.', 404);
        }

        return $this->success('Payout retrieved.', $this->serialize($payout));
    }

    protected function serialize(Payout $payout): array
    {
        return [
            'public_id' => $payout->public_id,
            'amount' => $payout->amount,
            'currency' => $payout->currency,
            'fee' => $payout->fee,
            'net_amount' => $payout->net_amount,
            'phone' => $payout->phone,
            'status' => $payout->status,
            'recipient_id' => $payout->recipient?->public_id,
            'failure_reason' => $payout->failure_reason,
            'created_at' => $payout->created_at,
        ];
    }

    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'errors' => null], $status);
    }

    protected function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'data' => null, 'errors' => []], $status);
    }
}
