<?php

namespace Tests\Feature;

use App\Jobs\ProcessPayout;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payments\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessPayoutJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_provider_success_marks_payout_success(): void
    {
        Bus::fake();
        [$merchant] = $this->merchantWithWallet(1000);
        $payout = app(PayoutService::class)->requestToPhone($merchant, '0712345678', 100);

        (new ProcessPayout($payout->id))->handle(app(\App\Services\Mpesa\B2CPayoutService::class), app(PayoutService::class));

        $this->assertSame('success', $payout->fresh()->status);
    }

    public function test_fake_provider_failure_marks_failed_and_reverses(): void
    {
        Bus::fake();
        config(['mpesa.b2c.fake_result' => 'failure']);
        [$merchant, $wallet] = $this->merchantWithWallet(1000);
        $payout = app(PayoutService::class)->requestToPhone($merchant, '0712345678', 100);

        (new ProcessPayout($payout->id))->handle(app(\App\Services\Mpesa\B2CPayoutService::class), app(PayoutService::class));

        $this->assertSame('reversed', $payout->fresh()->status);
        $this->assertSame(1000, $wallet->fresh()->available_balance);
    }

    public function test_missing_real_b2c_config_fails_safely(): void
    {
        Bus::fake();
        config(['mpesa.b2c.fake' => false]);
        [$merchant, $wallet] = $this->merchantWithWallet(1000);
        $payout = app(PayoutService::class)->requestToPhone($merchant, '0712345678', 100);

        (new ProcessPayout($payout->id))->handle(app(\App\Services\Mpesa\B2CPayoutService::class), app(PayoutService::class));

        $this->assertSame('reversed', $payout->fresh()->status);
        $this->assertSame(1000, $wallet->fresh()->available_balance);
    }

    protected function merchantWithWallet(int $balance): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ]);
        $wallet = Wallet::create(['merchant_id' => $merchant->id, 'available_balance' => $balance, 'pending_balance' => 0, 'currency' => 'KES']);

        return [$merchant, $wallet];
    }
}
