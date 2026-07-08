<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\Payments\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_api_key_can_create_invoice(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();

        $this->withHeaders(['x-api-key' => $secret])
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.id')
            ->assertJsonPath('data.customer_name', 'Jane Doe');
    }

    public function test_publishable_key_is_rejected(): void
    {
        $this->merchantWithApiKey();

        $this->withHeaders(['x-api-key' => 'pay_pk_test'])
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertStatus(401);
    }

    public function test_list_returns_only_authenticated_merchants_invoices(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        [$otherMerchant] = $this->merchantWithApiKey();
        app(InvoiceService::class)->create($merchant, $this->invoicePayload(['customer_name' => 'Mine']));
        app(InvoiceService::class)->create($otherMerchant, $this->invoicePayload(['customer_name' => 'Other']));

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson('/api/v1/invoices?search=Mine&status=draft')
            ->assertOk()
            ->assertJsonPath('data.items.0.customer_name', 'Mine')
            ->assertJsonMissing(['customer_name' => 'Other']);
    }

    public function test_retrieve_update_send_mark_paid_void_and_delete_work(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        $invoice = app(InvoiceService::class)->create($merchant, $this->invoicePayload());

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson("/api/v1/invoices/{$invoice->public_id}")
            ->assertOk()
            ->assertJsonPath('data.public_id', $invoice->public_id)
            ->assertJsonMissingPath('data.id');

        $this->withHeaders(['x-api-key' => $secret])
            ->patchJson("/api/v1/invoices/{$invoice->public_id}", $this->invoicePayload(['customer_name' => 'Updated']))
            ->assertOk()
            ->assertJsonPath('data.customer_name', 'Updated');

        $this->withHeaders(['x-api-key' => $secret])
            ->postJson("/api/v1/invoices/{$invoice->public_id}/send")
            ->assertOk()
            ->assertJsonPath('data.status', 'open');

        $this->withHeaders(['x-api-key' => $secret])
            ->postJson("/api/v1/invoices/{$invoice->public_id}/mark-paid")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $voidInvoice = app(InvoiceService::class)->create($merchant, $this->invoicePayload());
        $this->withHeaders(['x-api-key' => $secret])
            ->postJson("/api/v1/invoices/{$voidInvoice->public_id}/void")
            ->assertOk()
            ->assertJsonPath('data.status', 'void');

        $deleteInvoice = app(InvoiceService::class)->create($merchant, $this->invoicePayload());
        $this->withHeaders(['x-api-key' => $secret])
            ->deleteJson("/api/v1/invoices/{$deleteInvoice->public_id}")
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('invoices', ['public_id' => $deleteInvoice->public_id]);
    }

    protected function merchantWithApiKey(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);
        $secret = 'pay_sk_'.str()->random(32);

        ApiKey::create([
            'merchant_id' => $merchant->id,
            'name' => 'API Key',
            'environment' => 'sandbox',
            'publishable_key' => 'pay_pk_'.str()->random(24),
            'secret_key_hash' => bcrypt($secret),
            'secret_key_prefix' => substr($secret, 0, 10),
            'secret_key_last_four' => substr($secret, -4),
            'status' => 'active',
        ]);

        return [$merchant, $secret];
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
