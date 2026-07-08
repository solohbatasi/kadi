<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StkPushRequest;
use App\Services\Mpesa\StkPushService;
use App\Services\Payments\CommissionService;
use App\Services\Payments\TransactionService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected CommissionService $commissionService,
        protected StkPushService $stkPushService
    ) {
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

        return response()->json([
            'transaction_id' => $transaction->public_id,
            'status' => $transaction->status,
            'checkout_request_id' => $responsePayload['checkout_request_id'],
            'merchant_request_id' => $responsePayload['merchant_request_id'],
            'customer_message' => $responsePayload['customer_message'],
        ]);
    }
}
