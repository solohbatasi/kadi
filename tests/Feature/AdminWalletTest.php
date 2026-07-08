<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_wallets_and_view_wallet_ledger(): void
    {
        $admin = $this->adminUser();
        $merchant = $this->merchant();
        $wallet = $merchant->wallet()->create([
            'public_id' => 'wal_'.str()->random(16),
            'available_balance' => 1000,
            'pending_balance' => 0,
            'currency' => 'KES',
        ]);
        $wallet->ledgerEntries()->create([
            'merchant_id' => $merchant->id,
            'public_id' => 'wle_'.str()->random(16),
            'entry_type' => 'payment_credit',
            'direction' => 'credit',
            'amount' => 1000,
            'balance_after' => 1000,
        ]);

        $this->actingAs($admin)->get('/admin/wallets')->assertOk();
        $this->actingAs($admin)->get(route('admin.wallets.show', $wallet))->assertOk()->assertSee('payment_credit');
    }

    protected function adminUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->roles()->attach($role);

        return $user;
    }

    protected function merchant()
    {
        return User::factory()->withPersonalTeam()->create()->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Acme Pay',
            'status' => 'active',
        ]);
    }
}

