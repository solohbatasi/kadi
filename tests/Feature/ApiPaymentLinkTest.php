<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_api_key_can_create_payment_link(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();

        $response = $this->withHeaders(['x-api-key' => $secret])
            ->postJson('/api/v1/payment-links', [
                'title' => 'API Link',
                'amount' => 100,
                'allow_custom_amount' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.id')
            ->assertJsonPath('data.title', 'API Link');
    }

    public function test_publishable_key_is_rejected(): void
    {
        [$merchant] = $this->merchantWithApiKey();

        $this->withHeaders(['x-api-key' => 'pay_pk_test'])
            ->postJson('/api/v1/payment-links', ['title' => 'Bad', 'amount' => 100])
            ->assertStatus(401);
    }

    public function test_list_returns_only_authenticated_merchants_links(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        [$otherMerchant] = $this->merchantWithApiKey();
        $merchant->paymentLinks()->create($this->linkData(['title' => 'Mine']));
        $otherMerchant->paymentLinks()->create($this->linkData(['title' => 'Other']));

        $response = $this->withHeaders(['x-api-key' => $secret])
            ->getJson('/api/v1/payment-links');

        $response->assertOk()
            ->assertJsonPath('data.items.0.title', 'Mine')
            ->assertJsonMissing(['title' => 'Other']);
    }

    public function test_retrieve_by_public_id_works(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        $link = $merchant->paymentLinks()->create($this->linkData(['title' => 'Retrieve']));

        $this->withHeaders(['x-api-key' => $secret])
            ->getJson("/api/v1/payment-links/{$link->public_id}")
            ->assertOk()
            ->assertJsonPath('data.public_id', $link->public_id)
            ->assertJsonMissingPath('data.id');
    }

    public function test_update_works(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        $link = $merchant->paymentLinks()->create($this->linkData());

        $this->withHeaders(['x-api-key' => $secret])
            ->patchJson("/api/v1/payment-links/{$link->public_id}", [
                'title' => 'Updated API Link',
                'amount' => 250,
                'allow_custom_amount' => false,
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated API Link');
    }

    public function test_delete_works(): void
    {
        [$merchant, $secret] = $this->merchantWithApiKey();
        $link = $merchant->paymentLinks()->create($this->linkData());

        $this->withHeaders(['x-api-key' => $secret])
            ->deleteJson("/api/v1/payment-links/{$link->public_id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('payment_links', ['public_id' => $link->public_id]);
    }

    protected function merchantWithApiKey(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
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

    protected function linkData(array $overrides = []): array
    {
        return array_merge([
            'public_id' => 'plink_'.str()->random(16),
            'slug' => 'api-link-'.str()->random(8),
            'title' => 'API Payment Link',
            'amount' => 100,
            'currency' => 'KES',
            'allow_custom_amount' => false,
            'status' => 'active',
        ], $overrides);
    }
}
