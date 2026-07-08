<?php

namespace Tests\Feature;

use App\Models\Payout;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_payouts(): void
    {
        $admin = $this->adminUser();
        $merchant = $this->merchant();
        $payout = Payout::create([
            'merchant_id' => $merchant->id,
            'public_id' => 'po_'.str()->random(16),
            'amount' => 500,
            'currency' => 'KES',
            'fee' => 0,
            'net_amount' => 500,
            'phone' => '254716933897',
            'status' => 'pending',
            'provider' => 'mpesa',
        ]);

        $this->actingAs($admin)->get('/admin/payouts')->assertOk();
        $this->actingAs($admin)->get(route('admin.payouts.show', $payout))->assertOk()->assertSee('2547****897');
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

