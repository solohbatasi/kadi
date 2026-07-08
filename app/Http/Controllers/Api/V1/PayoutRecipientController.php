<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePayoutRecipientRequest;
use App\Models\PayoutRecipient;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayoutRecipientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $recipients = $request->attributes->get('merchant')
            ->payoutRecipients()
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->success('Payout recipients retrieved.', [
            'items' => collect($recipients->items())->map(fn ($recipient) => $this->serialize($recipient))->values(),
            'pagination' => [
                'current_page' => $recipients->currentPage(),
                'per_page' => $recipients->perPage(),
                'total' => $recipients->total(),
                'last_page' => $recipients->lastPage(),
            ],
        ]);
    }

    public function store(StorePayoutRecipientRequest $request): JsonResponse
    {
        $recipient = $request->attributes->get('merchant')->payoutRecipients()->create([
            'public_id' => $this->publicId(),
            'name' => $request->input('name'),
            'phone' => PhoneNumber::normalize($request->input('phone')),
            'status' => $request->input('status', 'active'),
        ]);

        return $this->success('Payout recipient created.', $this->serialize($recipient), 201);
    }

    public function update(StorePayoutRecipientRequest $request, string $publicId): JsonResponse
    {
        $recipient = $this->find($request, $publicId);

        if (! $recipient) {
            return $this->error('Payout recipient not found.', 404);
        }

        $recipient->update([
            'name' => $request->input('name'),
            'phone' => PhoneNumber::normalize($request->input('phone')),
            'status' => $request->input('status', $recipient->status),
        ]);

        return $this->success('Payout recipient updated.', $this->serialize($recipient->fresh()));
    }

    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $recipient = $this->find($request, $publicId);

        if (! $recipient) {
            return $this->error('Payout recipient not found.', 404);
        }

        $recipient->delete();

        return $this->success('Payout recipient deleted.');
    }

    protected function find(Request $request, string $publicId): ?PayoutRecipient
    {
        return $request->attributes->get('merchant')->payoutRecipients()->where('public_id', $publicId)->first();
    }

    protected function serialize(PayoutRecipient $recipient): array
    {
        return [
            'public_id' => $recipient->public_id,
            'name' => $recipient->name,
            'phone' => $recipient->phone,
            'status' => $recipient->status,
            'created_at' => $recipient->created_at,
        ];
    }

    protected function publicId(): string
    {
        do {
            $id = 'rec_'.Str::random(24);
        } while (PayoutRecipient::where('public_id', $id)->exists());

        return $id;
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
