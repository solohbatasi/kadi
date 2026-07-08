<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocalTestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleSidebarDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_receives_admin_role_data(): void
    {
        $this->seed(LocalTestingSeeder::class);
        $admin = User::where('email', 'admin@test.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.is_admin', true)
                ->where('auth.user.email', 'admin@test.local')
                ->where('auth.roles.0', 'admin')
            );
    }

    public function test_merchant_receives_safe_merchant_data(): void
    {
        $this->seed(LocalTestingSeeder::class);
        $merchantUser = User::where('email', 'live@test.local')->firstOrFail();

        $this->actingAs($merchantUser)
            ->get(route('developer.api-keys.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Developer/ApiKeys/Index')
                ->where('auth.is_admin', false)
                ->where('auth.user.email', 'live@test.local')
                ->where('auth.merchant.public_id', 'mer_local_live')
                ->where('auth.merchant.status', 'active')
                ->where('auth.merchant.compliance_status', 'verified')
                ->where('auth.merchant.live_enabled', true)
                ->missing('auth.merchant.id')
                ->missing('auth.merchant.document_number')
                ->missing('auth.merchant.kra_pin')
                ->missing('auth.merchant.secret_key_hash')
                ->missing('auth.merchant.webhook_secret')
            );
    }
}
