<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutRecipientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_manage_own_recipients_and_audit_logs_are_written(): void
    {
        [$user, $merchant] = $this->userAndMerchant();

        $this->actingAs($user)
            ->post('/developer/payout-recipients', ['name' => 'Jane', 'phone' => '0712345678'])
            ->assertRedirect();

        $recipient = $merchant->payoutRecipients()->first();
        $this->assertSame('254712345678', $recipient->phone);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payout_recipient.created']);

        $this->actingAs($user)
            ->put("/developer/payout-recipients/{$recipient->public_id}", ['name' => 'Jane Updated', 'phone' => '254700000001'])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'payout_recipient.updated']);

        $this->actingAs($user)->post("/developer/payout-recipients/{$recipient->public_id}/deactivate")->assertRedirect();
        $this->assertSame('inactive', $recipient->fresh()->status);

        $this->actingAs($user)->post("/developer/payout-recipients/{$recipient->public_id}/activate")->assertRedirect();
        $this->assertSame('active', $recipient->fresh()->status);

        $this->actingAs($user)->delete("/developer/payout-recipients/{$recipient->public_id}")->assertRedirect();
        $this->assertDatabaseMissing('payout_recipients', ['public_id' => $recipient->public_id]);
    }

    public function test_merchant_cannot_manage_another_merchants_recipient(): void
    {
        [$owner, $ownerMerchant] = $this->userAndMerchant();
        [$otherUser] = $this->userAndMerchant();
        $recipient = $ownerMerchant->payoutRecipients()->create([
            'public_id' => 'rec_'.str()->random(16),
            'name' => 'Other',
            'phone' => '254712345678',
            'status' => 'active',
        ]);

        $this->actingAs($otherUser)
            ->put("/developer/payout-recipients/{$recipient->public_id}", ['name' => 'Bad', 'phone' => '0711111111'])
            ->assertStatus(403);
    }

    protected function userAndMerchant(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ]);

        return [$user, $merchant];
    }
}
