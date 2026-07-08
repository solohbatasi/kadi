<?php

namespace Tests\Feature;

use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperPaymentLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_merchant_can_list_own_links(): void
    {
        [$user, $merchant] = $this->userAndMerchant();
        $link = $merchant->paymentLinks()->create($this->linkData(['title' => 'Own Link']));

        $this->actingAs($user)
            ->get('/developer/payment-links')
            ->assertStatus(200)
            ->assertSee('Developer/PaymentLinks/Index', false);
    }

    public function test_merchant_cannot_manage_another_merchants_link(): void
    {
        [$owner, $ownerMerchant] = $this->userAndMerchant();
        [$otherUser] = $this->userAndMerchant();
        $link = $ownerMerchant->paymentLinks()->create($this->linkData());

        $this->actingAs($otherUser)
            ->put("/developer/payment-links/{$link->public_id}", [
                'title' => 'Changed',
                'amount' => 200,
                'allow_custom_amount' => false,
            ])
            ->assertStatus(403);
    }

    public function test_create_update_activate_deactivate_delete_work_and_audit_logs_are_written(): void
    {
        [$user] = $this->userAndMerchant();

        $this->actingAs($user)
            ->post('/developer/payment-links', [
                'title' => 'Fees',
                'description' => 'Term fees',
                'amount' => 500,
                'allow_custom_amount' => false,
                'status' => 'active',
            ])
            ->assertRedirect();

        $link = PaymentLink::first();
        $this->assertNotNull($link);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_link.created']);

        $this->actingAs($user)
            ->put("/developer/payment-links/{$link->public_id}", [
                'title' => 'Updated Fees',
                'description' => 'Updated',
                'amount' => 700,
                'allow_custom_amount' => false,
                'status' => 'active',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_link.updated']);

        $this->actingAs($user)->post("/developer/payment-links/{$link->public_id}/deactivate")->assertRedirect();
        $this->assertSame('inactive', $link->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_link.deactivated']);

        $this->actingAs($user)->post("/developer/payment-links/{$link->public_id}/activate")->assertRedirect();
        $this->assertSame('active', $link->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_link.activated']);

        $this->actingAs($user)->delete("/developer/payment-links/{$link->public_id}")->assertRedirect();
        $this->assertDatabaseMissing('payment_links', ['public_id' => $link->public_id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_link.deleted']);
    }

    protected function userAndMerchant(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);

        return [$user, $merchant];
    }

    protected function linkData(array $overrides = []): array
    {
        return array_merge([
            'public_id' => 'plink_'.str()->random(16),
            'slug' => 'link-'.str()->random(8),
            'title' => 'Payment Link',
            'amount' => 100,
            'currency' => 'KES',
            'allow_custom_amount' => false,
            'status' => 'active',
        ], $overrides);
    }
}
