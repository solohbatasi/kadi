<?php

namespace Tests\Feature;

use App\Models\Payout;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payments\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class B2CCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_callback_marks_payout_success(): void
    {
        Bus::fake();
        config(['mpesa.callback_secret' => 'secret']);
        [$payout] = $this->processingPayout();

        $this->postJson('/api/mpesa/b2c/result/secret', $this->payload(0))->assertOk();

        $this->assertSame('success', $payout->fresh()->status);
    }

    public function test_failed_callback_reverses_and_duplicate_is_idempotent(): void
    {
        Bus::fake();
        config(['mpesa.callback_secret' => 'secret']);
        [$payout, $wallet] = $this->processingPayout();

        $this->postJson('/api/mpesa/b2c/result/secret', $this->payload(1, 'Failed'))->assertOk();
        $this->postJson('/api/mpesa/b2c/result/secret', $this->payload(1, 'Failed'))->assertOk();

        $this->assertSame('reversed', $payout->fresh()->status);
        $this->assertSame(1000, $wallet->fresh()->available_balance);
        $this->assertSame(1, $wallet->ledgerEntries()->where('entry_type', 'payout_reversal')->count());
    }

    public function test_invalid_secret_returns_401_and_unknown_conversation_returns_200(): void
    {
        config(['mpesa.callback_secret' => 'secret']);

        $this->postJson('/api/mpesa/b2c/result/wrong', $this->payload(0))->assertStatus(401);
        $this->postJson('/api/mpesa/b2c/result/secret', $this->payload(0))->assertOk();
    }

    protected function processingPayout(): array
    {
        [$merchant, $wallet] = $this->merchantWithWallet();
        $payout = app(PayoutService::class)->requestToPhone($merchant, '0712345678', 100);
        $payout->update([
            'status' => 'processing',
            'provider_conversation_id' => 'conv123',
            'provider_originator_conversation_id' => 'origin123',
        ]);

        return [$payout, $wallet];
    }

    protected function payload(int $code, string $description = 'Success'): array
    {
        return [
            'Result' => [
                'ConversationID' => 'conv123',
                'OriginatorConversationID' => 'origin123',
                'ResultCode' => $code,
                'ResultDesc' => $description,
            ],
        ];
    }

    protected function merchantWithWallet(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $merchant = $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'status' => 'active',
            'compliance_status' => 'verified',
            'live_enabled' => true,
        ]);
        $wallet = Wallet::create(['merchant_id' => $merchant->id, 'available_balance' => 1000, 'pending_balance' => 0, 'currency' => 'KES']);

        return [$merchant, $wallet];
    }
}
