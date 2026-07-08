<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payments\InvoiceService;
use App\Services\Payments\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoicePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_backing_payment_link_initiates_stk_push_with_invoice_metadata(): void
    {
        $this->fakeDaraja();
        [$merchant] = $this->merchant();
        $invoice = app(InvoiceService::class)->send(app(InvoiceService::class)->create($merchant, $this->invoicePayload()));

        $this->post("/pay/{$invoice->paymentLink->slug}", ['phone' => '0712345678'])
            ->assertOk()
            ->assertSee('Public/Pay/Pending', false);

        $transaction = Transaction::first();
        $this->assertSame('payment_link', $transaction->type);
        $this->assertSame($invoice->public_id, $transaction->metadata['invoice_public_id']);
        $this->assertSame($invoice->invoice_number, $transaction->metadata['invoice_number']);
        $this->assertSame($invoice->paymentLink->public_id, $transaction->metadata['payment_link_public_id']);
    }

    public function test_callback_success_marks_invoice_paid_and_credits_wallet_once(): void
    {
        $this->fakeDaraja();
        [$merchant] = $this->merchant();
        $wallet = Wallet::create([
            'merchant_id' => $merchant->id,
            'available_balance' => 0,
            'pending_balance' => 0,
            'currency' => 'KES',
        ]);
        $invoice = app(InvoiceService::class)->send(app(InvoiceService::class)->create($merchant, $this->invoicePayload()));

        $this->post("/pay/{$invoice->paymentLink->slug}", ['phone' => '0712345678'])->assertOk();
        $transaction = Transaction::first();

        $payload = [
            'checkout_request_id' => 'ws_CO_INVOICE',
            'merchant_request_id' => '123',
            'result_code' => '0',
            'result_description' => 'Success',
            'customer_message' => 'Success',
            'callback_metadata' => [
                'MpesaReceiptNumber' => 'RCP123',
            ],
        ];

        app(TransactionService::class)->processStkCallback($payload);
        app(TransactionService::class)->processStkCallback($payload);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->assertSame('inactive', $invoice->fresh()->paymentLink->status);
        $this->assertSame($transaction->net_amount, $wallet->fresh()->available_balance);
        $this->assertSame(1, $wallet->ledgerEntries()->count());
    }

    public function test_void_invoice_cannot_be_paid(): void
    {
        [$merchant] = $this->merchant();
        $invoice = app(InvoiceService::class)->void(app(InvoiceService::class)->create($merchant, $this->invoicePayload()));

        $this->get("/pay/{$invoice->paymentLink->slug}")
            ->assertOk()
            ->assertSee('Public/Pay/Unavailable', false);
    }

    protected function merchant(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);

        return [$merchant, $user];
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

    protected function fakeDaraja(): void
    {
        config([
            'mpesa.consumer_key' => 'consumer-key',
            'mpesa.consumer_secret' => 'consumer-secret',
            'mpesa.shortcode' => '174379',
            'mpesa.passkey' => 'passkey',
            'mpesa.callback_url' => 'https://paygate.test/api/mpesa/stk-callback/secret',
        ]);

        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'daraja-token'], 200),
            'sandbox.safaricom.co.ke/mpesa/stkpush/*' => Http::response([
                'MerchantRequestID' => '12345',
                'CheckoutRequestID' => 'ws_CO_INVOICE',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);
    }
}
