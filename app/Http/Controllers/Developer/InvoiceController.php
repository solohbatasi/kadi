<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\StoreInvoiceRequest;
use App\Http\Requests\Developer\UpdateInvoiceRequest;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Services\Payments\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoices)
    {
    }

    public function index(): Response
    {
        $merchant = auth()->user()->merchant()->first();

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        return Inertia::render('Developer/Invoices/Index', [
            'invoices' => $merchant->invoices()
                ->with('paymentLink')
                ->latest()
                ->get()
                ->map(fn (Invoice $invoice) => $this->serialize($invoice)),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $merchant = $request->user()->merchant()->first();

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $invoice = $this->invoices->create($merchant, $request->validated());
        $this->audit($invoice, 'invoice.created');

        return redirect()->route('developer.invoices.show', $invoice)
            ->with('flash.banner', 'Invoice created.');
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorizeOwnership($invoice);

        return Inertia::render('Developer/Invoices/Show', [
            'invoice' => $this->serialize($invoice->load('items', 'paymentLink')),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwnership($invoice);
        $invoice = $this->invoices->update($invoice->load('items', 'paymentLink'), $request->validated());
        $this->audit($invoice, 'invoice.updated');

        return back()->with('flash.banner', 'Invoice updated.');
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwnership($invoice);
        $invoice = $this->invoices->send($invoice->load('paymentLink'));
        $this->audit($invoice, 'invoice.sent');

        return back()->with('flash.banner', 'Invoice sent.');
    }

    public function markPaid(Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwnership($invoice);
        $invoice = $this->invoices->markPaid($invoice->load('paymentLink'));
        $this->audit($invoice, 'invoice.marked_paid');

        return back()->with('flash.banner', 'Invoice marked paid.');
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwnership($invoice);
        $invoice = $this->invoices->void($invoice->load('paymentLink'));
        $this->audit($invoice, 'invoice.voided');

        return back()->with('flash.banner', 'Invoice voided.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwnership($invoice);
        $this->audit($invoice, 'invoice.deleted');
        $this->invoices->delete($invoice->load('paymentLink'));

        return redirect()->route('developer.invoices.index')
            ->with('flash.banner', 'Invoice deleted.');
    }

    protected function authorizeOwnership(Invoice $invoice): void
    {
        $merchant = request()->user()?->merchant()->first();

        if (! $merchant || $invoice->merchant_id !== $merchant->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function serialize(Invoice $invoice): array
    {
        return [
            'public_id' => $invoice->public_id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'customer_phone' => $invoice->customer_phone,
            'currency' => $invoice->currency,
            'subtotal' => $invoice->subtotal,
            'tax_rate' => $invoice->tax_rate,
            'tax_amount' => $invoice->tax_amount,
            'discount_amount' => $invoice->discount_amount,
            'total' => $invoice->total,
            'status' => $invoice->status,
            'due_date' => $invoice->due_date,
            'notes' => $invoice->notes,
            'payment_url' => $invoice->paymentLink ? route('payment-links.pay.show', $invoice->paymentLink->slug) : null,
            'items' => $invoice->relationLoaded('items') ? $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])->values() : [],
            'created_at' => $invoice->created_at,
        ];
    }

    protected function audit(Invoice $invoice, string $action): void
    {
        AuditLog::create([
            'merchant_id' => $invoice->merchant_id,
            'user_id' => request()->user()?->id,
            'action' => $action,
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'invoice_public_id' => $invoice->public_id,
                'invoice_number' => $invoice->invoice_number,
            ],
            'created_at' => now(),
        ]);
    }
}
