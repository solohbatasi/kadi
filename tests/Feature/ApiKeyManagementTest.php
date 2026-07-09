<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApiKeyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_sandbox_api_key(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => false]);

        $this->actingAs($user)
            ->post('/developer/api-keys', [
                'name' => 'Sandbox Key',
                'environment' => 'sandbox',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('api_keys', [
            'merchant_id' => $merchant->id,
            'name' => 'Sandbox Key',
            'environment' => 'sandbox',
            'status' => 'active',
        ]);
    }

    public function test_production_key_creation_is_blocked_when_live_enabled_false(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => false]);

        $this->actingAs($user)
            ->post('/developer/api-keys', [
                'name' => 'Live Key',
                'environment' => 'production',
            ])
            ->assertSessionHasErrors(['environment']);

        $this->assertDatabaseCount('api_keys', 0);
    }

    public function test_secret_key_is_shown_once_and_hash_is_stored(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);

        $response = $this->actingAs($user)
            ->post('/developer/api-keys', [
                'name' => 'One-Time Key',
                'environment' => 'sandbox',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('api_key_secret');

        $apiKey = ApiKey::first();
        $this->assertNotNull($apiKey);
        $this->assertNotNull($apiKey->secret_key_hash);
        $this->assertStringStartsWith('pay_pk_', $apiKey->publishable_key);
        $this->assertDatabaseHas('api_keys', ['id' => $apiKey->id]);
    }

    public function test_created_secret_is_shared_with_api_keys_page_for_copying(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);

        $this->actingAs($user)
            ->post('/developer/api-keys', [
                'name' => 'Copyable Key',
                'environment' => 'sandbox',
            ])
            ->assertSessionHas('api_key_secret');

        $secret = session('api_key_secret');

        $this->actingAs($user)
            ->withSession(['api_key_secret' => $secret])
            ->get('/developer/api-keys')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Developer/ApiKeys/Index')
                ->where('api_key_secret', $secret)
            );
    }

    public function test_revoke_makes_key_unusable(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);

        $this->actingAs($user)
            ->post('/developer/api-keys', [
                'name' => 'Temp Key',
                'environment' => 'sandbox',
            ]);

        $apiKey = ApiKey::first();
        $this->post("/developer/api-keys/{$apiKey->id}/revoke");

        $this->assertDatabaseHas('api_keys', ['id' => $apiKey->id, 'status' => 'revoked']);
    }

    public function test_rotate_invalidates_old_secret_and_returns_new_secret(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);

        $this->actingAs($user)
            ->post('/developer/api-keys', [
                'name' => 'Rotate Key',
                'environment' => 'sandbox',
            ]);

        $apiKey = ApiKey::first();
        $oldSecret = session('api_key_secret');

        $this->post("/developer/api-keys/{$apiKey->id}/rotate")
            ->assertRedirect()
            ->assertSessionHas('api_key_secret');

        $newSecret = session('api_key_secret');
        $this->assertNotEquals($oldSecret, $newSecret);

        $this->assertDatabaseHas('api_keys', ['id' => $apiKey->id, 'status' => 'active']);
    }

    public function test_another_user_cannot_manage_this_merchants_key(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $user2 = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user1)
            ->post('/developer/api-keys', [
                'name' => 'User1 Key',
                'environment' => 'sandbox',
            ]);

        $apiKey = ApiKey::first();

        $this->actingAs($user2)
            ->post("/developer/api-keys/{$apiKey->id}/revoke")
            ->assertStatus(403);
    }
}
