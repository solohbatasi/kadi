<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_key_can_list_own_transactions(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        [$otherMerchant] = $this->merchantWithApiKey();

        $own = $this->createTransaction($merchant, ['reference' => 'ORDER-OWN']);
        $this->createTransaction($otherMerchant, ['reference' => 'ORDER-OTHER']);

        $response = $this->withHeaders(['x-api-key' => $secret])
            ->getJson('/api/v1/transactions');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.public_id', $own->public_id)
            ->assertJsonMissing(['reference' => 'ORDER-OTHER'])
            ->assertJsonMissingPath('data.items.0.id')
            ->assertJsonMissingPath('data.items.0.merchant_id');
    }

    public function test_secret_key_can_retrieve_transaction_by_public_id(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        $transaction = $this->createTransaction($merchant);

        $this->withHeaders(['Authorization' => "Bearer {$secret}"])
            ->getJson("/api/v1/transactions/{$transaction->public_id}")
            ->assertOk()
            ->assertJsonPath('data.public_id', $transaction->public_id)
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.merchant_id');
    }

    public function test_another_merchants_transaction_is_hidden(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        [$otherMerchant] = $this->merchantWithApiKey();
        $transaction = $this->createTransaction($otherMerchant);

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson("/api/v1/transactions/{$transaction->public_id}")
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_publishable_key_is_rejected(): void
    {
        $this->withHeaders(['x-api-key' => 'pay_pk_test'])
            ->getJson('/api/v1/transactions')
            ->assertUnauthorized();
    }

    public function test_filters_limit_transaction_results(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        $success = $this->createTransaction($merchant, ['status' => 'success', 'type' => 'payment_link']);
        $this->createTransaction($merchant, ['status' => 'failed', 'type' => 'stk_push']);

        $this->withHeaders(['Authorization' => "ApiKey {$secret}"])
            ->getJson('/api/v1/transactions?status=success&type=payment_link')
            ->assertOk()
            ->assertJsonPath('data.items.0.public_id', $success->public_id)
            ->assertJsonCount(1, 'data.items');
    }

    protected function merchantWithApiKey(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ]);
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

    protected function createTransaction($merchant, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
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
            'metadata' => ['order_id' => 'ORDER-001'],
        ], $overrides));
    }
}

