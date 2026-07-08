<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Merchant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(protected PaymentLinkService $paymentLinks)
    {
    }

    public function create(Merchant $merchant, array $data): Invoice
    {
        return DB::transaction(function () use ($merchant, $data) {
            $items = $this->normalizeItems($data['items'] ?? []);
            $totals = $this->calculateTotals($items, $data['tax_rate'] ?? 0, $data['discount_amount'] ?? 0);

            $invoice = $merchant->invoices()->create([
                'public_id' => $this->generatePublicId(),
                'invoice_number' => $this->generateInvoiceNumber($merchant),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'currency' => $data['currency'] ?? config('payments.currency', 'KES'),
                'subtotal' => $totals['subtotal'],
                'tax_rate' => $this->normalizeTaxRate($data['tax_rate'] ?? 0),
                'tax_amount' => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total' => $totals['total'],
                'status' => 'draft',
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->replaceItems($invoice, $items);
            $paymentLink = $this->createOrUpdatePaymentLink($invoice->fresh());
            $invoice->update(['payment_link_id' => $paymentLink->id]);

            return $invoice->fresh(['items', 'paymentLink']);
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $this->ensureEditable($invoice);

        return DB::transaction(function () use ($invoice, $data) {
            $items = $this->normalizeItems($data['items'] ?? $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->all());
            $totals = $this->calculateTotals($items, $data['tax_rate'] ?? $invoice->tax_rate, $data['discount_amount'] ?? $invoice->discount_amount);

            $invoice->update([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'currency' => $data['currency'] ?? $invoice->currency,
                'subtotal' => $totals['subtotal'],
                'tax_rate' => $this->normalizeTaxRate($data['tax_rate'] ?? $invoice->tax_rate),
                'tax_amount' => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total' => $totals['total'],
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? $invoice->metadata,
            ]);

            $this->replaceItems($invoice, $items);
            $this->createOrUpdatePaymentLink($invoice->fresh(['paymentLink']));

            return $invoice->fresh(['items', 'paymentLink']);
        });
    }

    public function send(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->status, ['draft', 'open'], true)) {
            throw new InvalidArgumentException('Only draft or open invoices can be sent.');
        }

        $invoice->update([
            'status' => 'open',
            'sent_at' => $invoice->sent_at ?? now(),
        ]);

        if ($invoice->paymentLink) {
            $this->paymentLinks->activate($invoice->paymentLink);
        }

        return $invoice->fresh(['items', 'paymentLink']);
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->status, ['draft', 'open'], true)) {
            return $invoice->fresh(['items', 'paymentLink']);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => $invoice->paid_at ?? now(),
        ]);

        if ($invoice->paymentLink) {
            $this->paymentLinks->deactivate($invoice->paymentLink);
        }

        return $invoice->fresh(['items', 'paymentLink']);
    }

    public function void(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->status, ['draft', 'open'], true)) {
            throw new InvalidArgumentException('Only draft or open invoices can be voided.');
        }

        $invoice->update([
            'status' => 'void',
            'voided_at' => $invoice->voided_at ?? now(),
        ]);

        if ($invoice->paymentLink) {
            $this->paymentLinks->deactivate($invoice->paymentLink);
        }

        return $invoice->fresh(['items', 'paymentLink']);
    }

    public function delete(Invoice $invoice): void
    {
        if ($invoice->status !== 'draft') {
            throw new InvalidArgumentException('Only draft invoices can be deleted.');
        }

        DB::transaction(function () use ($invoice) {
            if ($invoice->paymentLink) {
                $this->paymentLinks->delete($invoice->paymentLink);
            }

            $invoice->delete();
        });
    }

    public function recalculate(Invoice $invoice): Invoice
    {
        $items = $invoice->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->all();
        $totals = $this->calculateTotals($items, $invoice->tax_rate, $invoice->discount_amount);

        $invoice->update($totals);
        $this->createOrUpdatePaymentLink($invoice->fresh(['paymentLink']));

        return $invoice->fresh(['items', 'paymentLink']);
    }

    protected function ensureEditable(Invoice $invoice): void
    {
        if ($invoice->isLocked()) {
            throw new InvalidArgumentException('Paid and void invoices cannot be edited.');
        }
    }

    protected function replaceItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach ($items as $item) {
            $invoice->items()->create($item + [
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    }

    protected function createOrUpdatePaymentLink(Invoice $invoice)
    {
        $payload = [
            'title' => 'Invoice '.$invoice->invoice_number,
            'description' => $invoice->notes ?: 'Payment for invoice '.$invoice->invoice_number,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'allow_custom_amount' => false,
            'status' => in_array($invoice->status, ['paid', 'void'], true) ? 'inactive' : 'active',
            'metadata' => [
                'source' => 'invoice',
                'invoice_public_id' => $invoice->public_id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ];

        if ($invoice->paymentLink) {
            return $this->paymentLinks->update($invoice->paymentLink, $payload);
        }

        return $this->paymentLinks->create($invoice->merchant, $payload);
    }

    protected function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('At least one invoice item is required.');
        }

        return collect($items)->map(function (array $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = Money::toInteger($item['unit_price'] ?? 0);

            if ($unitPrice <= 0) {
                throw new InvalidArgumentException('Invoice item unit price must be greater than zero.');
            }

            return [
                'description' => (string) $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        })->all();
    }

    protected function calculateTotals(array $items, mixed $taxRate, mixed $discountAmount): array
    {
        $subtotal = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
        $discount = min(Money::toInteger($discountAmount ?? 0), $subtotal);
        $taxable = max(0, $subtotal - $discount);
        $taxAmount = $this->calculateTaxAmount($taxable, $taxRate);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount,
            'total' => max(0, $taxable + $taxAmount),
        ];
    }

    protected function calculateTaxAmount(int $amount, mixed $taxRate): int
    {
        $basisPoints = $this->taxRateToBasisPoints($taxRate);

        return intdiv(($amount * $basisPoints) + 9999, 10000);
    }

    protected function taxRateToBasisPoints(mixed $taxRate): int
    {
        $value = preg_replace('/[^0-9.]/', '', (string) $taxRate) ?: '0';
        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '00');

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    protected function normalizeTaxRate(mixed $taxRate): string
    {
        $basisPoints = $this->taxRateToBasisPoints($taxRate);

        return sprintf('%d.%02d', intdiv($basisPoints, 100), $basisPoints % 100);
    }

    protected function generateInvoiceNumber(Merchant $merchant): string
    {
        $next = $merchant->invoices()->count() + 1;

        do {
            $number = 'INV-'.str_pad((string) $next++, 6, '0', STR_PAD_LEFT);
        } while ($merchant->invoices()->where('invoice_number', $number)->exists());

        return $number;
    }

    protected function generatePublicId(): string
    {
        do {
            $publicId = 'inv_'.Str::random(24);
        } while (Invoice::where('public_id', $publicId)->exists());

        return $publicId;
    }
}
