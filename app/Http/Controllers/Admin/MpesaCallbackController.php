<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MpesaCallback;
use App\Support\Mask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MpesaCallbackController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['result_code', 'checkout_request_id', 'date_from', 'date_to']);

        $callbacks = MpesaCallback::query()
            ->with('transaction.merchant')
            ->when($filters['result_code'] ?? null, fn ($query, $code) => $query->where('result_code', $code))
            ->when($filters['checkout_request_id'] ?? null, fn ($query, $id) => $query->where('checkout_request_id', 'like', "%{$id}%"))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/MpesaCallbacks/Index', [
            'callbacks' => $callbacks->through(fn (MpesaCallback $callback) => $this->serialize($callback)),
            'filters' => $filters,
        ]);
    }

    public function show(MpesaCallback $callback): Response
    {
        $callback->load('transaction.merchant');

        return Inertia::render('Admin/MpesaCallbacks/Show', [
            'callback' => array_merge($this->serialize($callback), [
                'raw_payload' => $this->sanitizePayload($callback->raw_payload ?? []),
            ]),
        ]);
    }

    protected function serialize(MpesaCallback $callback): array
    {
        return [
            'id' => $callback->id,
            'transaction_public_id' => $callback->transaction?->public_id,
            'merchant' => $callback->transaction?->merchant ? [
                'public_id' => $callback->transaction->merchant->public_id,
                'business_name' => $callback->transaction->merchant->business_name,
            ] : null,
            'checkout_request_id' => $callback->checkout_request_id,
            'merchant_request_id' => $callback->merchant_request_id,
            'result_code' => $callback->result_code,
            'result_description' => $callback->result_description,
            'processed_at' => $callback->processed_at,
            'created_at' => $callback->created_at,
        ];
    }

    protected function sanitizePayload(array $payload): array
    {
        return Mask::arraySensitive($payload);
    }
}
