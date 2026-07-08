<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocalTestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteAccessByRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->seed(LocalTestingSeeder::class);

        $admin = User::where('email', 'admin@test.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_merchant_cannot_access_admin_dashboard(): void
    {
        $this->seed(LocalTestingSeeder::class);

        $merchant = User::where('email', 'live@test.local')->firstOrFail();

        $this->actingAs($merchant)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_merchant_can_access_developer_api_keys(): void
    {
        $this->seed(LocalTestingSeeder::class);

        $merchant = User::where('email', 'live@test.local')->firstOrFail();

        $this->actingAs($merchant)
            ->get('/developer/api-keys')
            ->assertOk();
    }

    public function test_guest_cannot_access_developer_dashboard(): void
    {
        $this->get('/developer')
            ->assertRedirect('/login');
    }

    public function test_suspended_merchant_api_key_is_rejected(): void
    {
        $this->seed(LocalTestingSeeder::class);

        $this->getJson('/api/v1/transactions', [
            'x-api-key' => 'pay_sk_local_suspended_00000000000000000000000000001',
        ])->assertForbidden()
            ->assertJson(['message' => 'Merchant account is not active.']);
    }
}
