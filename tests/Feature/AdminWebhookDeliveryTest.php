<?php

namespace Tests\Feature;

use App\Models\MerchantWebhookDelivery;
use App\Models\MerchantWebhookEndpoint;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminWebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_view_and_retry_deliveries_without_exposing_secret(): void
    {
        Queue::fake();
        $admin = $this->adminUser();
        $merchant = $this->merchant();
        MerchantWebhookEndpoint::create([
            'merchant_id' => $merchant->id,
            'url' => 'https://example.test/webhook',
            'secret' => 'whsec_super_secret',
            'is_enabled' => true,
        ]);
        $delivery = MerchantWebhookDelivery::create([
            'merchant_id' => $merchant->id,
            'event' => 'transaction.success',
            'url' => 'https://example.test/webhook',
            'status' => 'failed',
            'attempts' => 1,
            'payload' => ['event' => 'transaction.success'],
            'error_message' => 'timeout',
        ]);

        $this->actingAs($admin)->get('/admin/webhook-deliveries')->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.webhook-deliveries.show', $delivery))
            ->assertOk()
            ->assertDontSee('whsec_super_secret');

        $this->actingAs($admin)->post(route('admin.webhook-deliveries.retry', $delivery))->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'webhook_delivery.retry_queued', 'merchant_id' => $merchant->id]);
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

