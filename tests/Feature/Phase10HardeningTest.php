<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\IdempotencyKey;
use App\Models\MerchantWebhookEndpoint;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLedgerEntry;
use App\Support\Mask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase10HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_api_rate_limit_returns_consistent_json_response(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();

        for ($i = 0; $i < 120; $i++) {
            $this->withHeaders(['x-api-key' => $secret])->getJson('/api/v1/transactions')->assertOk();
        }

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson('/api/v1/transactions')
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Too many requests.');
    }

    public function test_public_pay_submit_is_rate_limited_by_ip(): void
    {
        Route::post('/_test/public-pay-rate-limit', fn () => response('ok'))
            ->middleware('throttle:public-payment-submit');

        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->post('/_test/public-pay-rate-limit')->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post('/_test/public-pay-rate-limit')
            ->assertStatus(429);
    }

    public function test_pending_transaction_timeout_command_marks_only_stale_pending_transactions(): void
    {
        Queue::fake();
        $merchant = $this->merchant();
        MerchantWebhookEndpoint::create([
            'merchant_id' => $merchant->id,
            'url' => 'https://example.test/webhook',
            'secret' => 'whsec_secret',
            'is_enabled' => true,
        ]);

        $stale = $this->transaction($merchant, ['created_at' => now()->subMinutes(20), 'updated_at' => now()->subMinutes(20)]);
        $fresh = $this->transaction($merchant, ['created_at' => now(), 'updated_at' => now()]);
        $success = $this->transaction($merchant, ['status' => 'success', 'created_at' => now()->subMinutes(20), 'updated_at' => now()->subMinutes(20)]);

        $this->artisan('payments:expire-pending-transactions --minutes=15')
            ->expectsOutput('Expired 1 pending transaction(s).')
            ->assertExitCode(0);

        $this->assertSame('timeout', $stale->fresh()->status);
        $this->assertSame('pending', $fresh->fresh()->status);
        $this->assertSame('success', $success->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'transaction.timeout_expired', 'subject_id' => $stale->id]);
        $this->assertDatabaseMissing('wallet_ledger_entries', ['transaction_id' => $stale->id]);
        $this->assertDatabaseHas('merchant_webhook_deliveries', ['transaction_id' => $stale->id, 'event' => 'transaction.timeout']);
    }

    public function test_idempotency_cleanup_deletes_only_expired_keys(): void
    {
        $merchant = $this->merchant();
        $expired = IdempotencyKey::create($this->idempotencyData($merchant, ['key' => 'expired', 'expires_at' => now()->subMinute()]));
        $active = IdempotencyKey::create($this->idempotencyData($merchant, ['key' => 'active', 'expires_at' => now()->addDay()]));

        $this->artisan('payments:cleanup-idempotency-keys')
            ->expectsOutput('Deleted 1 expired idempotency key(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('idempotency_keys', ['id' => $expired->id]);
        $this->assertDatabaseHas('idempotency_keys', ['id' => $active->id]);
    }

    public function test_reconciliation_report_outputs_expected_metrics(): void
    {
        $merchant = $this->merchant();
        $wallet = $merchant->wallet()->create(['available_balance' => 0, 'pending_balance' => 0, 'currency' => 'KES']);
        $transaction = $this->transaction($merchant, ['status' => 'success', 'amount' => 1000, 'commission_amount' => 20, 'net_amount' => 980]);
        WalletLedgerEntry::create([
            'wallet_id' => $wallet->id,
            'merchant_id' => $merchant->id,
            'transaction_id' => $transaction->id,
            'public_id' => 'wle_'.str()->random(16),
            'entry_type' => 'payment_credit',
            'direction' => 'credit',
            'amount' => 980,
            'balance_after' => 980,
        ]);

        $this->artisan('payments:reconciliation-report')
            ->expectsOutputToContain('successful_transaction_gross_volume')
            ->expectsOutputToContain('wallet_ledger_credit_total')
            ->assertExitCode(0);
    }

    public function test_security_check_fails_for_critical_production_misconfiguration_without_printing_secrets(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'app.debug' => true,
            'app.key' => '',
            'app.url' => 'http://example.test',
            'mpesa.callback_secret' => '',
            'mpesa.consumer_secret' => 'super-secret-value',
            'queue.default' => 'sync',
        ]);

        $this->artisan('payments:security-check')
            ->expectsOutputToContain('[FAIL]')
            ->doesntExpectOutputToContain('super-secret-value')
            ->assertExitCode(1);
    }

    public function test_masking_helper_masks_sensitive_values(): void
    {
        $masked = Mask::arraySensitive([
            'phone' => '254716933897',
            'consumer_secret' => 'secret-value',
            'nested' => ['Authorization' => 'Bearer token'],
        ]);

        $this->assertSame('2547****897', Mask::phone('254716933897'));
        $this->assertStringContainsString('pay_sk_', Mask::apiKey('pay_sk_test_1234567890'));
        $this->assertSame('2547****897', $masked['phone']);
        $this->assertStringStartsWith('[redacted:', $masked['consumer_secret']);
        $this->assertStringStartsWith('[redacted:', $masked['nested']['Authorization']);
    }

    public function test_selected_api_response_does_not_expose_obvious_secrets_or_internal_ids(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        $transaction = $this->transaction($merchant, ['metadata' => ['consumer_secret' => 'hidden']]);

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson("/api/v1/transactions/{$transaction->public_id}")
            ->assertOk()
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.merchant_id')
            ->assertJsonMissing(['secret_key_hash'])
            ->assertJsonMissing(['consumer_secret' => 'hidden']);
    }

    protected function merchantWithApiKey(): array
    {
        $merchant = $this->merchant();
        $secret = 'pay_sk_'.str()->random(32);

        ApiKey::create([
            'merchant_id' => $merchant->id,
            'name' => 'API Key',
            'environment' => 'sandbox',
            'publishable_key' => 'pay_pk_'.str()->random(24),
            'secret_key_hash' => bcrypt($secret),
            'secret_key_prefix' => substr($secret, 0, 10),
            'secret_key_last_four' => substr($secret, -4),
            'status' => 'active',
        ]);

        return [$merchant, $secret];
    }

    protected function merchant()
    {
        $user = User::factory()->withPersonalTeam()->create();

        return $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Hardening Merchant',
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ]);
    }

    protected function transaction($merchant, array $overrides = []): Transaction
    {
        $timestamps = array_intersect_key($overrides, array_flip(['created_at', 'updated_at']));
        $attributes = array_diff_key($overrides, $timestamps);

        $transaction = Transaction::create(array_merge([
            'merchant_id' => $merchant->id,
            'public_id' => 'txn_'.str()->random(16),
            'type' => 'stk_push',
            'direction' => 'credit',
            'environment' => 'sandbox',
            'phone' => '254716933897',
            'amount' => 800,
            'currency' => 'KES',
            'commission_amount' => 20,
            'provider_fee' => 0,
            'net_amount' => 780,
            'status' => 'pending',
            'reference' => 'ORDER-001',
            'description' => 'Ticket payment',
            'metadata' => [],
        ], $attributes));

        if ($timestamps !== []) {
            $transaction->forceFill($timestamps)->save();
        }

        return $transaction;
    }

    protected function idempotencyData($merchant, array $overrides = []): array
    {
        return array_merge([
            'merchant_id' => $merchant->id,
            'key' => 'idem_'.str()->random(8),
            'method' => 'POST',
            'path' => '/api/v1/transactions/push-stk',
            'request_hash' => hash('sha256', str()->random(8)),
            'response_body' => ['ok' => true],
            'status_code' => 200,
            'expires_at' => now()->addDay(),
        ], $overrides);
    }
}
