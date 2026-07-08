<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PayPaymentLinkRequest;
use App\Models\PaymentLink;
use App\Services\Mpesa\StkPushService;
use App\Services\Payments\CommissionService;
use App\Services\Payments\MerchantWebhookService;
use App\Services\Payments\PaymentLinkService;
use App\Services\Payments\TransactionService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentLinkPayController extends Controller
{
    public function __construct(
        protected PaymentLinkService $paymentLinks,
        protected TransactionService $transactions,
        protected CommissionService $commissions,
        protected StkPushService $stkPush,
        protected MerchantWebhookService $webhooks
    ) {
    }

    public function show(string $slug): Response
    {
        $paymentLink = PaymentLink::with('merchant')->where('slug', $slug)->first();

        if (! $paymentLink || $paymentLink->status !== 'active') {
            return Inertia::render('Public/Pay/Unavailable');
        }

        return Inertia::render('Public/Pay/Show', [
            'paymentLink' => $this->serializePublic($paymentLink),
            'minimumAmount' => Money::minimumAmount(),
        ]);
    }

    public function pay(PayPaymentLinkRequest $request, string $slug): Response
    {
        $paymentLink = $this->paymentLinks->findActiveBySlug($slug);

        if (! $paymentLink) {
            return Inertia::render('Public/Pay/Unavailable');
        }

        $phone = PhoneNumber::normalize($request->input('phone'));
        $amount = $paymentLink->allow_custom_amount
            ? Money::toInteger($request->input('amount'))
            : $paymentLink->amount;
        $commission = $this->commissions->calculate($paymentLink->merchant, $amount);
        $netAmount = max(0, $amount - $commission);
        $idempotencyKey = 'plink_'.Str::uuid()->toString();

        $transaction = $this->transactions->createPendingPaymentLink(
            $paymentLink,
            $phone,
            $amount,
            $commission,
            $netAmount,
            $idempotencyKey,
            config('mpesa.environment', 'sandbox')
        );

        $responsePayload = $this->stkPush->requestStkPush(
            $transaction,
            $phone,
            $amount,
            $idempotencyKey,
            $transaction->environment
        );

        $transaction->update([
            'mpesa_merchant_request_id' => $responsePayload['merchant_request_id'],
            'mpesa_checkout_request_id' => $responsePayload['checkout_request_id'],
            'metadata' => array_merge($transaction->metadata ?? [], [
                'stk_push_response' => $responsePayload,
            ]),
        ]);

        $transaction = $transaction->fresh();
        $this->webhooks->dispatchTransactionEvent($transaction, 'transaction.pending');

        return Inertia::render('Public/Pay/Pending', [
            'paymentLink' => $this->serializePublic($paymentLink),
            'transaction' => [
                'id' => $transaction->public_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
            ],
            'customerMessage' => $responsePayload['customer_message'],
        ]);
    }

    protected function serializePublic(PaymentLink $paymentLink): array
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
            'merchant_name' => $paymentLink->merchant?->business_name ?: config('app.name', 'PayGate'),
        ];
    }
}
