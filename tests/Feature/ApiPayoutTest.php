<?php

namespace Tests\Feature;

use App\Jobs\ProcessPayout;
use App\Models\ApiKey;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ApiPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_api_key_can_request_payout(): void
    {
        Bus::fake();
        [$merchant, $secret] = $this->merchantWithApiKeyAndWallet(1000);

        $this->withHeaders(['x-api-key' => $secret])
            ->postJson('/api/v1/payouts', ['amount' => 100, 'phone' => '0712345678'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.id');

        $this->assertSame(900, $merchant->wallet->fresh()->available_balance);
        Bus::assertDispatched(ProcessPayout::class);
    }

    public function test_publishable_key_is_rejected(): void
    {
        $this->merchantWithApiKeyAndWallet(1000);

        $this->withHeaders(['x-api-key' => 'pay_pk_test'])
            ->postJson('/api/v1/payouts', ['amount' => 100, 'phone' => '0712345678'])
            ->assertStatus(401);
    }

    public function test_list_and_retrieve_are_merchant_scoped(): void
    {
        Bus::fake();
        [$merchant, $secret] = $this->merchantWithApiKeyAndWallet(1000);
        [$otherMerchant] = $this->merchantWithApiKeyAndWallet(1000);
        $payout = app(\App\Services\Payments\PayoutService::class)->requestToPhone($merchant, '0712345678', 100);
        app(\App\Services\Payments\PayoutService::class)->requestToPhone($otherMerchant, '0711111111', 100);

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson('/api/v1/payouts?status=pending')
            ->assertOk()
            ->assertJsonPath('data.items.0.public_id', $payout->public_id)
            ->assertJsonMissingPath('data.items.0.id');

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson("/api/v1/payouts/{$payout->public_id}")
            ->assertOk()
            ->assertJsonPath('data.public_id', $payout->public_id)
            ->assertJsonMissingPath('data.id');
    }

    public function test_api_can_manage_payout_recipients(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKeyAndWallet(1000);

        $response = $this->withHeaders(['x-api-key' => $secret])
            ->postJson('/api/v1/payout-recipients', ['name' => 'Jane', 'phone' => '0712345678'])
            ->assertCreated()
            ->assertJsonPath('data.phone', '254712345678');

        $publicId = $response->json('data.public_id');

        $this->withHeaders(['x-api-key' => $secret])
            ->patchJson("/api/v1/payout-recipients/{$publicId}", ['name' => 'Jane Updated', 'phone' => '0711111111'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Jane Updated');

        $this->withHeaders(['x-api-key' => $secret])
            ->deleteJson("/api/v1/payout-recipients/{$publicId}")
            ->assertOk();
    }

    protected function merchantWithApiKeyAndWallet(int $balance): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ]);
        $wallet = Wallet::create(['merchant_id' => $merchant->id, 'available_balance' => $balance, 'pending_balance' => 0, 'currency' => 'KES']);
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

        $merchant->setRelation('wallet', $wallet);

        return [$merchant, $secret];
    }
}
