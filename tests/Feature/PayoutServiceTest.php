<?php

namespace Tests\Feature;

use App\Jobs\ProcessPayout;
use App\Models\Payout;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payments\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_payout_and_debits_wallet(): void
    {
        Bus::fake();
        [$merchant, $wallet] = $this->merchantWithWallet(1000);

        $payout = app(PayoutService::class)->requestToPhone($merchant, '0712345678', 300);

        $this->assertStringStartsWith('po_', $payout->public_id);
        $this->assertSame('pending', $payout->status);
        $this->assertSame(700, $wallet->fresh()->available_balance);
        $this->assertDatabaseHas('wallet_ledger_entries', ['entry_type' => 'payout_debit', 'amount' => 300]);
        Bus::assertDispatched(ProcessPayout::class);
    }

    public function test_rejects_insufficient_balance_and_below_minimum(): void
    {
        Bus::fake();
        [$merchant] = $this->merchantWithWallet(100);

        $this->expectException(RuntimeException::class);
        app(PayoutService::class)->requestToPhone($merchant, '0712345678', 300);
    }

    public function test_rejects_amount_below_minimum(): void
    {
        Bus::fake();
        config(['payments.min_payout_amount' => 50]);
        [$merchant] = $this->merchantWithWallet(1000);

        $this->expectException(InvalidArgumentException::class);
        app(PayoutService::class)->requestToPhone($merchant, '0712345678', 10);
    }

    public function test_rejects_inactive_and_unverified_when_not_allowed(): void
    {
        Bus::fake();
        [$merchant] = $this->merchantWithWallet(1000, ['status' => 'suspended']);

        $this->expectException(InvalidArgumentException::class);
        app(PayoutService::class)->requestToPhone($merchant, '0712345678', 100);
    }

    public function test_rejects_incomplete_compliance_when_config_disallows(): void
    {
        Bus::fake();
        config(['payments.allow_sandbox_payouts_without_verified_compliance' => false]);
        [$merchant] = $this->merchantWithWallet(1000, ['compliance_status' => 'incomplete']);

        $this->expectException(InvalidArgumentException::class);
        app(PayoutService::class)->requestToPhone($merchant, '0712345678', 100);
    }

    public function test_failed_payout_reverses_once_and_success_does_not_alter_wallet_again(): void
    {
        Bus::fake();
        [$merchant, $wallet] = $this->merchantWithWallet(1000);
        $service = app(PayoutService::class);
        $payout = $service->requestToPhone($merchant, '0712345678', 300);

        $service->markFailed($payout, 'Failed');
        $service->markFailed($payout->fresh(), 'Failed again');

        $this->assertSame(1000, $wallet->fresh()->available_balance);
        $this->assertSame(1, $wallet->ledgerEntries()->where('entry_type', 'payout_reversal')->count());

        $success = $service->requestToPhone($merchant, '0712345678', 200);
        $service->markSuccess($success);
        $this->assertSame(800, $wallet->fresh()->available_balance);
    }

    protected function merchantWithWallet(int $balance, array $overrides = []): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create(array_merge([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ], $overrides));
        $wallet = Wallet::create([
            'merchant_id' => $merchant->id,
            'available_balance' => $balance,
            'pending_balance' => 0,
            'currency' => 'KES',
        ]);

        return [$merchant, $wallet, $user];
    }
}
