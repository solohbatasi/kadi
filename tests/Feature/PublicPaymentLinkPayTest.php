<?php

namespace Tests\Feature;

use App\Jobs\DeliverMerchantWebhook;
use App\Models\MerchantWebhookDelivery;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicPaymentLinkPayTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_renders_active_link(): void
    {
        $link = $this->paymentLink();

        $this->get("/pay/{$link->slug}")
            ->assertStatus(200)
            ->assertSee('Public/Pay/Show', false);
    }

    public function test_inactive_link_shows_unavailable_page(): void
    {
        $link = $this->paymentLink(['status' => 'inactive']);

        $this->get("/pay/{$link->slug}")
            ->assertStatus(200)
            ->assertSee('Public/Pay/Unavailable', false);
    }

    public function test_fixed_amount_link_initiates_stk_push_and_does_not_credit_wallet(): void
    {
        Bus::fake();
        $this->fakeDaraja();
        $link = $this->paymentLink(['amount' => 300]);
        $link->merchant->webhookEndpoint()->create([
            'url' => 'https://merchant.test/webhook',
            'secret' => 'webhook-secret-value',
            'is_enabled' => true,
        ]);
        $wallet = Wallet::create([
            'merchant_id' => $link->merchant_id,
            'available_balance' => 0,
            'pending_balance' => 0,
            'currency' => 'KES',
        ]);

        $this->post("/pay/{$link->slug}", ['phone' => '0712345678'])
            ->assertStatus(200)
            ->assertSee('Public/Pay/Pending', false);

        $transaction = Transaction::first();
        $this->assertSame('payment_link', $transaction->type);
        $this->assertSame(300, $transaction->amount);
        $this->assertSame($link->public_id, $transaction->metadata['payment_link_public_id']);
        $this->assertSame(0, $wallet->fresh()->available_balance);
        $this->assertDatabaseHas('merchant_webhook_deliveries', ['event' => 'transaction.pending']);
        Bus::assertDispatched(DeliverMerchantWebhook::class);
    }

    public function test_custom_amount_link_requires_amount(): void
    {
        $link = $this->paymentLink(['allow_custom_amount' => true, 'amount' => null]);

        $this->post("/pay/{$link->slug}", ['phone' => '0712345678'])
            ->assertSessionHasErrors(['amount']);
    }

    public function test_amount_below_minimum_is_rejected(): void
    {
        config(['payments.min_stk_amount' => 10]);
        $link = $this->paymentLink(['allow_custom_amount' => true, 'amount' => null]);

        $this->post("/pay/{$link->slug}", ['phone' => '0712345678', 'amount' => 1])
            ->assertSessionHasErrors(['amount']);
    }

    protected function paymentLink(array $overrides = [])
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);

        return $merchant->paymentLinks()->create(array_merge([
            'public_id' => 'plink_'.str()->random(16),
            'slug' => 'pay-'.str()->random(8),
            'title' => 'Pay Merchant',
            'amount' => 100,
            'currency' => 'KES',
            'allow_custom_amount' => false,
            'status' => 'active',
        ], $overrides));
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
                'CheckoutRequestID' => 'ws_CO_LINK',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);
    }
}
