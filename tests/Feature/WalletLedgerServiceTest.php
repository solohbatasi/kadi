<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_increases_wallet_balance(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);
        $wallet = $merchant->wallet ?? Wallet::create(['merchant_id' => $merchant->id, 'public_id' => 'wal_test', 'available_balance' => 0, 'pending_balance' => 0, 'currency' => 'KES']);

        $service = app(\App\Services\Payments\WalletLedgerService::class);
        $entry = $service->credit($wallet, 500, 'Credit test');

        $this->assertEquals(500, $entry->balance_after);
        $this->assertEquals(500, $wallet->fresh()->available_balance);
    }

    public function test_debit_decreases_wallet_balance(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);
        $wallet = $merchant->wallet ?? Wallet::create(['merchant_id' => $merchant->id, 'public_id' => 'wal_test', 'available_balance' => 1000, 'pending_balance' => 0, 'currency' => 'KES']);

        $service = app(\App\Services\Payments\WalletLedgerService::class);
        $entry = $service->debit($wallet, 300, 'Debit test');

        $this->assertEquals(700, $entry->balance_after);
        $this->assertEquals(700, $wallet->fresh()->available_balance);
    }

    public function test_debit_with_insufficient_balance_fails(): void
    {
        $this->expectException(\RuntimeException::class);

        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);
        $wallet = $merchant->wallet ?? Wallet::create(['merchant_id' => $merchant->id, 'public_id' => 'wal_test', 'available_balance' => 100, 'pending_balance' => 0, 'currency' => 'KES']);

        $service = app(\App\Services\Payments\WalletLedgerService::class);
        $service->debit($wallet, 200, 'Insufficient funds');
    }

    public function test_amount_less_than_or_equal_to_zero_fails(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);
        $wallet = $merchant->wallet ?? Wallet::create(['merchant_id' => $merchant->id, 'public_id' => 'wal_test', 'available_balance' => 100, 'pending_balance' => 0, 'currency' => 'KES']);

        $service = app(\App\Services\Payments\WalletLedgerService::class);
        $service->credit($wallet, 0, 'Zero amount');
    }
}
