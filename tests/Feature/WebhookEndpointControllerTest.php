<?php

namespace Tests\Feature;

use App\Jobs\DeliverMerchantWebhook;
use App\Models\MerchantWebhookDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebhookEndpointControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_settings_routes_require_authentication(): void
    {
        $this->get('/developer/webhooks')->assertRedirect('/login');
        $this->put('/developer/webhooks', [])->assertRedirect('/login');
        $this->post('/developer/webhooks/test')->assertRedirect('/login');
    }

    public function test_webhook_settings_do_not_expose_secret(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $this->createMerchantFor($user);
        $merchant->webhookEndpoint()->create([
            'url' => 'https://merchant.test/webhook',
            'secret' => 'super-secret-webhook-value',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)
            ->get('/developer/webhooks')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Developer/Webhooks/Show')
                ->where('endpoint.url', 'https://merchant.test/webhook')
                ->where('endpoint.is_enabled', true)
                ->where('endpoint.has_secret', true)
                ->missing('endpoint.secret')
            );
    }

    public function test_user_updates_only_their_own_webhook_endpoint(): void
    {
        $firstUser = User::factory()->withPersonalTeam()->create();
        $firstMerchant = $this->createMerchantFor($firstUser, 'mer_first');
        $firstEndpoint = $firstMerchant->webhookEndpoint()->create([
            'url' => 'https://first.test/webhook',
            'secret' => 'first-secret-value',
            'is_enabled' => true,
        ]);

        $secondUser = User::factory()->withPersonalTeam()->create();
        $secondMerchant = $this->createMerchantFor($secondUser, 'mer_second');
        $secondEndpoint = $secondMerchant->webhookEndpoint()->create([
            'url' => 'https://second.test/webhook',
            'secret' => 'second-secret-value',
            'is_enabled' => false,
        ]);

        $this->actingAs($secondUser)
            ->put('/developer/webhooks', [
                'url' => 'https://updated-second.test/webhook',
                'secret' => 'updated-second-secret-value',
                'is_enabled' => true,
            ])
            ->assertRedirect();

        $this->assertSame('https://first.test/webhook', $firstEndpoint->fresh()->url);
        $this->assertSame('https://updated-second.test/webhook', $secondEndpoint->fresh()->url);
        $this->assertTrue($secondEndpoint->fresh()->is_enabled);
    }

    public function test_test_webhook_endpoint_queues_delivery_without_exposing_secret(): void
    {
        Bus::fake();

        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $this->createMerchantFor($user);
        $merchant->webhookEndpoint()->create([
            'url' => 'https://merchant.test/webhook',
            'secret' => 'super-secret-webhook-value',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)
            ->post('/developer/webhooks/test')
            ->assertRedirect();

        $delivery = MerchantWebhookDelivery::first();

        $this->assertNotNull($delivery);
        $this->assertSame($merchant->id, $delivery->merchant_id);
        $this->assertSame('transaction.pending', $delivery->event);
        $this->assertStringNotContainsString('super-secret-webhook-value', json_encode($delivery->payload));

        Bus::assertDispatched(DeliverMerchantWebhook::class);
    }

    protected function createMerchantFor(User $user, string $publicId = 'mer_test')
    {
        return $user->merchant()->create([
            'public_id' => $publicId.'_'.str()->random(8),
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);
    }
}
