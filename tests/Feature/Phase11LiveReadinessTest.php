<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\MerchantWebhookDelivery;
use App\Models\MerchantWebhookEndpoint;
use App\Models\Payout;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Operations\OperationalAlertService;
use App\Services\Payments\MerchantReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class Phase11LiveReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_service_detects_incomplete_and_complete_items(): void
    {
        $merchant = $this->merchant();
        $service = app(MerchantReadinessService::class);

        $this->assertFalse($service->checklist($merchant)['items']['business_profile_completed']);
        $this->assertFalse($service->canRequestLive($merchant));

        $this->completeMerchant($merchant);
        ApiKey::create($this->apiKeyData($merchant));
        MerchantWebhookEndpoint::updateOrCreate(['merchant_id' => $merchant->id], [
            'url' => 'https://example.test/webhook',
            'secret' => 'whsec_test',
            'is_enabled' => true,
        ]);
        Transaction::create($this->transactionData($merchant));

        $checklist = $service->checklist($merchant->fresh());
        $this->assertTrue($checklist['items']['sandbox_api_key_created']);
        $this->assertTrue($checklist['items']['webhook_configured']);
        $this->assertTrue($checklist['can_request_live']);
    }

    public function test_merchant_can_submit_compliance_without_exposing_sensitive_values(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $this->merchant($user);

        $response = $this->actingAs($user)->post(route('developer.compliance.submit'), $this->compliancePayload());

        $response->assertRedirect(route('developer.onboarding.index'));
        $this->assertDatabaseHas('merchants', ['id' => $merchant->id, 'compliance_status' => 'pending_review']);
        $this->assertDatabaseHas('audit_logs', ['merchant_id' => $merchant->id, 'action' => 'merchant.compliance_submitted']);

        $this->actingAs($user)
            ->get(route('developer.compliance.edit'))
            ->assertOk()
            ->assertDontSee('ID123456')
            ->assertDontSee('A123456789Z');
    }

    public function test_live_mode_request_and_admin_review_flow(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $this->merchant($user);

        $this->actingAs($user)
            ->post(route('developer.live-mode.request'))
            ->assertSessionHasErrors('live_mode');

        $this->completeMerchant($merchant);
        ApiKey::create($this->apiKeyData($merchant));

        $this->actingAs($user)
            ->post(route('developer.live-mode.request'))
            ->assertRedirect();
        $this->assertNotNull($merchant->fresh()->live_requested_at);

        $admin = $this->adminUser();
        $this->actingAs($admin)
            ->post(route('admin.merchants.approve-live', $merchant))
            ->assertRedirect();
        $this->assertTrue($merchant->fresh()->live_enabled);

        $merchant->update(['live_enabled' => false]);
        $this->actingAs($admin)
            ->post(route('admin.merchants.reject-live', $merchant), ['reason' => 'Missing provider approval'])
            ->assertRedirect();
        $this->assertSame('Missing provider approval', $merchant->fresh()->live_rejection_reason);

        $this->actingAs($user)
            ->post(route('admin.merchants.approve-live', $merchant))
            ->assertForbidden();
    }

    public function test_public_legal_pages_render(): void
    {
        $this->get('/terms')->assertOk()->assertInertia(fn ($page) => $page->component('Public/Legal/Terms'));
        $this->get('/privacy')->assertOk()->assertInertia(fn ($page) => $page->component('Public/Legal/Privacy'));
        $this->get('/acceptable-use')->assertOk()->assertInertia(fn ($page) => $page->component('Public/Legal/AcceptableUse'));
        $this->get('/security')->assertOk()->assertInertia(fn ($page) => $page->component('Public/Legal/Security'));
    }

    public function test_operational_alert_service_detects_thresholds_without_secrets(): void
    {
        Log::spy();
        config([
            'operations.failed_webhook_threshold' => 1,
            'operations.pending_payout_threshold' => 1,
            'operations.failed_payout_threshold' => 99,
            'operations.stale_transaction_threshold' => 99,
            'operations.alert_email' => null,
        ]);
        $merchant = $this->merchant();
        MerchantWebhookDelivery::create([
            'merchant_id' => $merchant->id,
            'event' => 'transaction.success',
            'url' => 'https://example.test/webhook?secret=should-mask',
            'status' => 'failed',
            'payload' => ['phone' => '254716933897', 'secret' => 'hidden'],
        ]);
        Payout::create([
            'merchant_id' => $merchant->id,
            'public_id' => 'po_'.str()->random(16),
            'amount' => 100,
            'currency' => 'KES',
            'fee' => 0,
            'net_amount' => 100,
            'phone' => '254716933897',
            'status' => 'pending',
        ]);

        $alerts = app(OperationalAlertService::class)->check();

        $this->assertCount(2, $alerts);
        $this->assertStringNotContainsString('254716933897', json_encode($alerts));
        $this->assertStringNotContainsString('hidden', json_encode($alerts));
    }

    public function test_admin_failed_jobs_view_is_redacted_and_admin_only(): void
    {
        $admin = $this->adminUser();
        $normal = User::factory()->withPersonalTeam()->create();
        DB::table('failed_jobs')->insert([
            'uuid' => (string) str()->uuid(),
            'connection' => 'database',
            'queue' => 'webhooks',
            'payload' => json_encode(['displayName' => 'Job', 'secret' => 'raw-secret', 'phone' => '254716933897']),
            'exception' => 'RuntimeException: webhook failed',
            'failed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.failed-jobs.index'))
            ->assertOk()
            ->assertSee('webhooks')
            ->assertDontSee('raw-secret')
            ->assertDontSee('254716933897');

        $this->actingAs($normal)
            ->get(route('admin.failed-jobs.index'))
            ->assertForbidden();
    }

    protected function adminUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->roles()->attach($role);

        return $user;
    }

    protected function merchant(?User $user = null)
    {
        $user ??= User::factory()->withPersonalTeam()->create();

        return $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => null,
            'business_email' => null,
            'business_phone' => null,
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => false,
        ]);
    }

    protected function completeMerchant($merchant): void
    {
        $merchant->update([
            'business_name' => 'Acme Pay',
            'business_email' => 'ops@example.test',
            'business_phone' => '254716933897',
            'business_type' => 'limited_company',
            'platform_url' => 'https://example.test',
            'compliance_status' => 'pending_review',
        ]);

        $merchant->profile()->updateOrCreate([], [
            'owner_name' => 'Amina Otieno',
            'owner_email' => 'amina@example.test',
            'owner_phone' => '254716933897',
            'document_type' => 'national_id',
            'document_number' => 'ID123456',
            'kra_pin' => 'A123456789Z',
            'address' => 'Nairobi',
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
        ]);
    }

    protected function compliancePayload(): array
    {
        return [
            'business_name' => 'Acme Pay',
            'business_email' => 'ops@example.test',
            'business_phone' => '0716933897',
            'business_type' => 'limited_company',
            'platform_url' => 'https://example.test',
            'owner_name' => 'Amina Otieno',
            'owner_email' => 'amina@example.test',
            'owner_phone' => '0716933897',
            'document_type' => 'national_id',
            'document_number' => 'ID123456',
            'kra_pin' => 'A123456789Z',
            'address' => 'Nairobi',
            'payout_phone' => '0716933897',
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ];
    }

    protected function apiKeyData($merchant): array
    {
        $secret = 'pay_sk_'.str()->random(32);

        return [
            'merchant_id' => $merchant->id,
            'name' => 'Sandbox',
            'environment' => 'sandbox',
            'publishable_key' => 'pay_pk_'.str()->random(24),
            'secret_key_hash' => bcrypt($secret),
            'secret_key_prefix' => substr($secret, 0, 10),
            'secret_key_last_four' => substr($secret, -4),
            'status' => 'active',
        ];
    }

    protected function transactionData($merchant): array
    {
        return [
            'merchant_id' => $merchant->id,
            'public_id' => 'txn_'.str()->random(16),
            'type' => 'stk_push',
            'direction' => 'credit',
            'environment' => 'sandbox',
            'phone' => '254716933897',
            'amount' => 100,
            'currency' => 'KES',
            'commission_amount' => 2,
            'net_amount' => 98,
            'status' => 'pending',
        ];
    }
}

