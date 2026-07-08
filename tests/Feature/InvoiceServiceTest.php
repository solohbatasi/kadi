<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payments\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_draft_invoice_with_backing_payment_link(): void
    {
        $invoice = app(InvoiceService::class)->create($this->merchant(), $this->invoicePayload());

        $this->assertStringStartsWith('inv_', $invoice->public_id);
        $this->assertSame('draft', $invoice->status);
        $this->assertNotNull($invoice->paymentLink);
        $this->assertSame($invoice->total, $invoice->paymentLink->amount);
        $this->assertSame($invoice->public_id, $invoice->paymentLink->metadata['invoice_public_id']);
    }

    public function test_calculates_subtotal_tax_discount_and_total(): void
    {
        $invoice = app(InvoiceService::class)->create($this->merchant(), $this->invoicePayload([
            'tax_rate' => '10',
            'discount_amount' => 20,
            'items' => [
                ['description' => 'A', 'quantity' => 2, 'unit_price' => 100],
            ],
        ]));

        $this->assertSame(200, $invoice->subtotal);
        $this->assertSame(20, $invoice->discount_amount);
        $this->assertSame(18, $invoice->tax_amount);
        $this->assertSame(198, $invoice->total);
    }

    public function test_send_mark_paid_and_void_transitions(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $service->create($this->merchant(), $this->invoicePayload());

        $invoice = $service->send($invoice);
        $this->assertSame('open', $invoice->status);
        $this->assertNotNull($invoice->sent_at);

        $invoice = $service->markPaid($invoice);
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame('inactive', $invoice->paymentLink->status);

        $voidInvoice = $service->create($this->merchant(), $this->invoicePayload());
        $voidInvoice = $service->void($voidInvoice);
        $this->assertSame('void', $voidInvoice->status);
        $this->assertSame('inactive', $voidInvoice->paymentLink->status);
    }

    public function test_delete_only_draft_invoice(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $service->create($this->merchant(), $this->invoicePayload());
        $publicId = $invoice->public_id;

        $service->delete($invoice);
        $this->assertDatabaseMissing('invoices', ['public_id' => $publicId]);

        $openInvoice = $service->send($service->create($this->merchant(), $this->invoicePayload()));
        $this->expectException(InvalidArgumentException::class);
        $service->delete($openInvoice);
    }

    public function test_paid_invoice_cannot_be_edited_destructively(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $service->markPaid($service->create($this->merchant(), $this->invoicePayload()));

        $this->expectException(InvalidArgumentException::class);
        $service->update($invoice, $this->invoicePayload(['customer_name' => 'Changed']));
    }

    protected function merchant()
    {
        $user = User::factory()->withPersonalTeam()->create();

        return $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);
    }

    protected function invoicePayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '0712345678',
            'tax_rate' => '0',
            'discount_amount' => 0,
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 100],
            ],
        ], $overrides);
    }
}
