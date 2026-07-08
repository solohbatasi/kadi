<?php

namespace Tests\Feature;

use App\Jobs\ProcessPayout;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeveloperPayoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_view_payouts_and_request_payout(): void
    {
        Bus::fake();
        [$user, $merchant, $wallet] = $this->userAndMerchantWithWallet(1000);

        $this->actingAs($user)->get('/developer/payouts')->assertOk()->assertSee('Developer/Payouts/Index', false);

        $this->actingAs($user)
            ->post('/developer/payouts', ['amount' => 200, 'phone' => '0712345678'])
            ->assertRedirect();

        $this->assertSame(800, $wallet->fresh()->available_balance);
        $this->assertSame(1, $merchant->payouts()->count());
        Bus::assertDispatched(ProcessPayout::class);
    }

    public function test_payout_above_balance_fails_and_wallet_is_not_debited(): void
    {
        Bus::fake();
        [$user, $merchant, $wallet] = $this->userAndMerchantWithWallet(100);

        $this->actingAs($user)
            ->post('/developer/payouts', ['amount' => 200, 'phone' => '0712345678'])
            ->assertServerError();

        $this->assertSame(100, $wallet->fresh()->available_balance);
        $this->assertSame(0, $merchant->payouts()->count());
    }

    protected function userAndMerchantWithWallet(int $balance): array
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

        return [$user, $merchant, $wallet];
    }
}
