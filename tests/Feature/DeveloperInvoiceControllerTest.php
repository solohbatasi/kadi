<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Payments\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperInvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_list_own_invoices(): void
    {
        [$user, $merchant] = $this->userAndMerchant();
        app(InvoiceService::class)->create($merchant, $this->invoicePayload());

        $this->actingAs($user)
            ->get('/developer/invoices')
            ->assertOk()
            ->assertSee('Developer/Invoices/Index', false);
    }

    public function test_merchant_cannot_manage_another_merchants_invoice(): void
    {
        [$owner, $ownerMerchant] = $this->userAndMerchant();
        [$otherUser] = $this->userAndMerchant();
        $invoice = app(InvoiceService::class)->create($ownerMerchant, $this->invoicePayload());

        $this->actingAs($otherUser)
            ->put("/developer/invoices/{$invoice->public_id}", $this->invoicePayload(['customer_name' => 'Changed']))
            ->assertStatus(403);
    }

    public function test_create_update_send_mark_paid_void_and_delete_work_with_audits(): void
    {
        [$user] = $this->userAndMerchant();

        $this->actingAs($user)
            ->post('/developer/invoices', $this->invoicePayload())
            ->assertRedirect();

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.created']);

        $this->actingAs($user)
            ->put("/developer/invoices/{$invoice->public_id}", $this->invoicePayload(['customer_name' => 'Updated Customer']))
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.updated']);

        $this->actingAs($user)->post("/developer/invoices/{$invoice->public_id}/send")->assertRedirect();
        $this->assertSame('open', $invoice->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.sent']);

        $this->actingAs($user)->post("/developer/invoices/{$invoice->public_id}/mark-paid")->assertRedirect();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.marked_paid']);

        $draft = app(InvoiceService::class)->create($user->merchant()->first(), $this->invoicePayload());
        $this->actingAs($user)->post("/developer/invoices/{$draft->public_id}/void")->assertRedirect();
        $this->assertSame('void', $draft->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.voided']);

        $deleteDraft = app(InvoiceService::class)->create($user->merchant()->first(), $this->invoicePayload());
        $this->actingAs($user)->delete("/developer/invoices/{$deleteDraft->public_id}")->assertRedirect();
        $this->assertDatabaseMissing('invoices', ['public_id' => $deleteDraft->public_id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.deleted']);
    }

    protected function userAndMerchant(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);

        return [$user, $merchant];
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
