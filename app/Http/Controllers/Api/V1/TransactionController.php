<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StkPushRequest;
use App\Models\Transaction;
use App\Services\Mpesa\StkPushService;
use App\Services\Payments\CommissionService;
use App\Services\Payments\MerchantWebhookService;
use App\Services\Payments\TransactionService;
use App\Support\Mask;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected CommissionService $commissionService,
        protected StkPushService $stkPushService,
        protected MerchantWebhookService $webhooks
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchant');

        $transactions = $merchant->transactions()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->query('date_from'), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->success('Transactions retrieved.', [
            'items' => collect($transactions->items())->map(fn (Transaction $transaction) => $this->serialize($transaction))->values(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    public function initiateStkPush(StkPushRequest $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchant');
        $environment = $request->attributes->get('apiEnvironment');
        $phone = PhoneNumber::normalize($request->input('phone'));
        $amount = $request->validatedAmount();
        $reference = $request->input('reference');
        $description = $request->input('description', 'M-Pesa STK Push');
        $idempotencyKey = $request->header('Idempotency-Key') ?? $request->header('idempotency-key');

        if (empty($idempotencyKey)) {
            return response()->json(['message' => 'Idempotency-Key header is required.'], 422);
        }

        $commissionAmount = $this->commissionService->calculate($merchant, $amount);
        $netAmount = max(0, $amount - $commissionAmount);

        $transaction = $this->transactionService->createPendingStkPush(
            $merchant,
            $phone,
            $amount,
            config('payments.currency', 'KES'),
            $commissionAmount,
            $netAmount,
            $reference,
            $description,
            $idempotencyKey,
            $environment
        );

        $responsePayload = $this->stkPushService->requestStkPush(
            $transaction,
            $phone,
            $amount,
            $idempotencyKey,
            $environment
        );

        $transaction->update([
            'mpesa_merchant_request_id' => $responsePayload['merchant_request_id'],
            'mpesa_checkout_request_id' => $responsePayload['checkout_request_id'],
            'metadata' => array_merge($transaction->metadata ?? [], [
                'stk_push_response' => $responsePayload,
            ]),
        ]);

        $this->webhooks->dispatchTransactionEvent($transaction->fresh(), 'transaction.pending');

        return response()->json([
            'transaction_id' => $transaction->public_id,
            'status' => $transaction->status,
            'checkout_request_id' => $responsePayload['checkout_request_id'],
            'merchant_request_id' => $responsePayload['merchant_request_id'],
            'customer_message' => $responsePayload['customer_message'],
        ]);
    }

    public function show(Request $request, string $publicId): JsonResponse
    {
        $transaction = $request->attributes->get('merchant')
            ->transactions()
            ->where('public_id', $publicId)
            ->first();

        if (! $transaction) {
            return $this->error('Transaction not found.', 404);
        }

        return $this->success('Transaction retrieved.', $this->serialize($transaction));
    }

    protected function serialize(Transaction $transaction): array
    {
        return [
            'public_id' => $transaction->public_id,
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'environment' => $transaction->environment,
            'phone' => PhoneNumber::mask($transaction->phone),
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'commission_amount' => $transaction->commission_amount,
            'provider_fee' => $transaction->provider_fee,
            'net_amount' => $transaction->net_amount,
            'status' => $transaction->status,
            'reference' => $transaction->reference,
            'description' => $transaction->description,
            'checkout_request_id' => $transaction->mpesa_checkout_request_id,
            'merchant_request_id' => $transaction->mpesa_merchant_request_id,
            'mpesa_receipt' => $transaction->mpesa_receipt_number,
            'result_code' => $transaction->mpesa_result_code,
            'result_description' => $transaction->mpesa_result_description,
            'metadata' => Mask::arraySensitive($transaction->metadata ?? []),
            'paid_at' => $transaction->paid_at,
            'failed_at' => $transaction->failed_at,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
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
