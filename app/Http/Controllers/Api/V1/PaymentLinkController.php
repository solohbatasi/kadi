<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentLinkRequest;
use App\Http\Requests\Api\V1\UpdatePaymentLinkRequest;
use App\Models\PaymentLink;
use App\Services\Payments\PaymentLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function __construct(protected PaymentLinkService $paymentLinks)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchant');
        $status = $request->query('status');

        $links = $merchant->paymentLinks()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->success('Payment links retrieved.', [
            'items' => collect($links->items())->map(fn (PaymentLink $paymentLink) => $this->serialize($paymentLink))->values(),
            'pagination' => [
                'current_page' => $links->currentPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
                'last_page' => $links->lastPage(),
            ],
        ]);
    }

    public function store(StorePaymentLinkRequest $request): JsonResponse
    {
        $paymentLink = $this->paymentLinks->create(
            $request->attributes->get('merchant'),
            $request->validated()
        );

        return $this->success('Payment link created.', $this->serialize($paymentLink), 201);
    }

    public function show(Request $request, string $publicId): JsonResponse
    {
        $paymentLink = $this->findForMerchant($request, $publicId);

        if (! $paymentLink) {
            return $this->error('Payment link not found.', 404);
        }

        return $this->success('Payment link retrieved.', $this->serialize($paymentLink));
    }

    public function update(UpdatePaymentLinkRequest $request, string $publicId): JsonResponse
    {
        $paymentLink = $this->findForMerchant($request, $publicId);

        if (! $paymentLink) {
            return $this->error('Payment link not found.', 404);
        }

        $paymentLink = $this->paymentLinks->update($paymentLink, $request->validated());

        return $this->success('Payment link updated.', $this->serialize($paymentLink));
    }

    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $paymentLink = $this->findForMerchant($request, $publicId);

        if (! $paymentLink) {
            return $this->error('Payment link not found.', 404);
        }

        $this->paymentLinks->delete($paymentLink);

        return $this->success('Payment link deleted.');
    }

    protected function findForMerchant(Request $request, string $publicId): ?PaymentLink
    {
        return $request->attributes->get('merchant')
            ->paymentLinks()
            ->where('public_id', $publicId)
            ->first();
    }

    protected function serialize(PaymentLink $paymentLink): array
    {
        return [
            'public_id' => $paymentLink->public_id,
            'slug' => $paymentLink->slug,
            'title' => $paymentLink->title,
            'description' => $paymentLink->description,
            'amount' => $paymentLink->amount,
            'currency' => $paymentLink->currency,
            'allow_custom_amount' => $paymentLink->allow_custom_amount,
            'success_redirect_url' => $paymentLink->success_redirect_url,
            'status' => $paymentLink->status,
            'public_url' => route('payment-links.pay.show', $paymentLink->slug),
            'created_at' => $paymentLink->created_at,
        ];
    }

    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    protected function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => [],
        ], $status);
    }
}
