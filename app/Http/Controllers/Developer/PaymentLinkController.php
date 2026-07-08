<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\StorePaymentLinkRequest;
use App\Http\Requests\Developer\UpdatePaymentLinkRequest;
use App\Models\AuditLog;
use App\Models\PaymentLink;
use App\Services\Payments\PaymentLinkService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentLinkController extends Controller
{
    public function __construct(protected PaymentLinkService $paymentLinks)
    {
    }

    public function index(): Response
    {
        $merchant = auth()->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        return Inertia::render('Developer/PaymentLinks/Index', [
            'paymentLinks' => $merchant->paymentLinks()
                ->latest()
                ->get()
                ->map(fn (PaymentLink $paymentLink) => $this->serialize($paymentLink)),
        ]);
    }

    public function store(StorePaymentLinkRequest $request): RedirectResponse
    {
        $merchant = $request->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $paymentLink = $this->paymentLinks->create($merchant, $request->validated());
        $this->audit($paymentLink, 'payment_link.created');

        return redirect()
            ->route('developer.payment-links.show', $paymentLink)
            ->with('flash.banner', 'Payment link created.');
    }

    public function show(PaymentLink $paymentLink): Response
    {
        $this->authorizeOwnership($paymentLink);

        return Inertia::render('Developer/PaymentLinks/Show', [
            'paymentLink' => $this->serialize($paymentLink),
        ]);
    }

    public function update(UpdatePaymentLinkRequest $request, PaymentLink $paymentLink): RedirectResponse
    {
        $this->authorizeOwnership($paymentLink);

        $paymentLink = $this->paymentLinks->update($paymentLink, $request->validated());
        $this->audit($paymentLink, 'payment_link.updated');

        return back()->with('flash.banner', 'Payment link updated.');
    }

    public function activate(PaymentLink $paymentLink): RedirectResponse
    {
        $this->authorizeOwnership($paymentLink);
        $this->paymentLinks->activate($paymentLink);
        $this->audit($paymentLink, 'payment_link.activated');

        return back()->with('flash.banner', 'Payment link activated.');
    }

    public function deactivate(PaymentLink $paymentLink): RedirectResponse
    {
        $this->authorizeOwnership($paymentLink);
        $this->paymentLinks->deactivate($paymentLink);
        $this->audit($paymentLink, 'payment_link.deactivated');

        return back()->with('flash.banner', 'Payment link deactivated.');
    }

    public function destroy(PaymentLink $paymentLink): RedirectResponse
    {
        $this->authorizeOwnership($paymentLink);
        $this->audit($paymentLink, 'payment_link.deleted');
        $this->paymentLinks->delete($paymentLink);

        return redirect()
            ->route('developer.payment-links.index')
            ->with('flash.banner', 'Payment link deleted.');
    }

    protected function authorizeOwnership(PaymentLink $paymentLink): void
    {
        $merchant = request()->user()?->merchant;

        if (! $merchant || $paymentLink->merchant_id !== $merchant->id) {
            abort(403, 'Unauthorized action.');
        }
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

    protected function audit(PaymentLink $paymentLink, string $action): void
    {
        AuditLog::create([
            'merchant_id' => $paymentLink->merchant_id,
            'user_id' => request()->user()?->id,
            'action' => $action,
            'subject_type' => PaymentLink::class,
            'subject_id' => $paymentLink->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'payment_link_public_id' => $paymentLink->public_id,
                'slug' => $paymentLink->slug,
            ],
            'created_at' => now(),
        ]);
    }
}
