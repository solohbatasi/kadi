<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveApiKey(User $user): ApiKey
    {
        $merchant = $user->merchant ?? $user->merchant()->create([
            'public_id' => 'mer_test',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);

        $response = $this->actingAs($user)
            ->post('/developer/api-keys', [
                'name' => 'Auth Key',
                'environment' => 'sandbox',
            ]);

        return ApiKey::first();
    }

    public function test_valid_x_api_key_authenticates(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $apiKey = $this->createActiveApiKey($user);

        $response = $this->getJson('/api/test-api-key', ['x-api-key' => session('api_key_secret')]);

        $response->assertStatus(200);
    }

    public function test_valid_authorization_bearer_authenticates(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $apiKey = $this->createActiveApiKey($user);

        $response = $this->getJson('/api/test-api-key', ['Authorization' => 'Bearer '.session('api_key_secret')]);

        $response->assertStatus(200);
    }

    public function test_valid_authorization_apikey_authenticates(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $apiKey = $this->createActiveApiKey($user);

        $response = $this->getJson('/api/test-api-key', ['Authorization' => 'ApiKey '.session('api_key_secret')]);

        $response->assertStatus(200);
    }

    public function test_publishable_key_is_rejected(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);

        $response = $this->getJson('/api/test-api-key', ['x-api-key' => 'pay_pk_invalid']);

        $response->assertStatus(401);
    }

    public function test_revoked_key_is_rejected(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $apiKey = $this->createActiveApiKey($user);

        $this->actingAs($user)->post("/developer/api-keys/{$apiKey->id}/revoke");

        $response = $this->getJson('/api/test-api-key', ['x-api-key' => session('api_key_secret')]);

        $response->assertStatus(401);
    }

    public function test_invalid_key_is_rejected(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->getJson('/api/test-api-key', ['x-api-key' => 'pay_sk_invalid']);

        $response->assertStatus(401);
    }

    public function test_suspended_merchant_is_rejected(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant ?? $user->merchant()->create(['public_id' => 'mer_test', 'status' => 'active', 'compliance_status' => 'incomplete', 'live_enabled' => true]);

        $this->actingAs($user)
            ->post('/developer/api-keys', ['name' => 'Auth Key', 'environment' => 'sandbox'])
            ->assertRedirect();

        $secret = session('api_key_secret');
        $merchant->update(['status' => 'suspended']);

        $response = $this->getJson('/api/test-api-key', ['x-api-key' => $secret]);

        $response->assertStatus(403);
    }
}
