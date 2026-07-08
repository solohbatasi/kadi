<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMerchantTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_merchants(): void
    {
        $admin = $this->adminUser();
        $merchant = $this->merchant();

        $this->actingAs($admin)->get('/admin/merchants')->assertOk();
        $this->actingAs($admin)->get(route('admin.merchants.show', $merchant))->assertOk();
    }

    public function test_admin_can_activate_suspend_and_toggle_live_mode(): void
    {
        $admin = $this->adminUser();
        $merchant = $this->merchant(['status' => 'pending', 'live_enabled' => false]);

        $this->actingAs($admin)->post(route('admin.merchants.activate', $merchant))->assertRedirect();
        $this->assertDatabaseHas('merchants', ['id' => $merchant->id, 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.merchants.suspend', $merchant))->assertRedirect();
        $this->assertDatabaseHas('merchants', ['id' => $merchant->id, 'status' => 'suspended']);

        $this->actingAs($admin)->post(route('admin.merchants.enable-live', $merchant))->assertRedirect();
        $this->assertDatabaseHas('merchants', ['id' => $merchant->id, 'live_enabled' => true]);
    }

    public function test_admin_can_update_compliance_and_audit_logs_are_written(): void
    {
        $admin = $this->adminUser();
        $merchant = $this->merchant(['compliance_status' => 'pending_review']);

        $this->actingAs($admin)->post(route('admin.merchants.verify-compliance', $merchant))->assertRedirect();

        $this->assertDatabaseHas('merchants', ['id' => $merchant->id, 'compliance_status' => 'verified']);
        $this->assertDatabaseHas('audit_logs', [
            'merchant_id' => $merchant->id,
            'user_id' => $admin->id,
            'action' => 'merchant.compliance_verified',
        ]);
    }

    protected function adminUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->roles()->attach($role);

        return $user;
    }

    protected function merchant(array $overrides = [])
    {
        $user = User::factory()->withPersonalTeam()->create();

        return $user->merchant()->create(array_merge([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Acme Pay',
            'business_email' => 'ops@example.test',
            'business_phone' => '254716933897',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => false,
        ], $overrides));
    }
}

