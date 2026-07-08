<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_transactions_with_masked_phone(): void
    {
        $admin = $this->adminUser();
        $merchant = $this->merchant();
        $transaction = $this->transaction($merchant);

        $this->actingAs($admin)->get('/admin/transactions')->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('2547****897')
            ->assertDontSee('254716933897')
            ->assertDontSee('secret_key_hash');
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

    protected function transaction($merchant): Transaction
    {
        return Transaction::create([
            'merchant_id' => $merchant->id,
            'public_id' => 'txn_'.str()->random(16),
            'type' => 'stk_push',
            'direction' => 'credit',
            'environment' => 'sandbox',
            'phone' => '254716933897',
            'amount' => 800,
            'currency' => 'KES',
            'commission_amount' => 20,
            'net_amount' => 780,
            'status' => 'success',
            'reference' => 'ORDER-001',
        ]);
    }
}

