<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_dashboard_bootstraps_merchant_resources(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/developer')
            ->assertStatus(200);

        $user->refresh();

        $this->assertNotNull($user->merchant);
        $this->assertNotNull($user->merchant->profile);
        $this->assertNotNull($user->merchant->wallet);
        $this->assertNotNull($user->merchant->webhookEndpoint);
    }

    public function test_developer_dashboard_does_not_duplicate_bootstrapped_resources(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)->get('/developer');

        $this->actingAs($user)->get('/developer');

        $merchant = $user->refresh()->merchant;

        $this->assertEquals(1, $merchant->profile()->count());
        $this->assertEquals(1, $merchant->wallet()->count());
        $this->assertEquals(1, $merchant->webhookEndpoint()->count());
    }
}
