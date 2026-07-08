<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\PaymentLink;
use App\Models\Payout;
use App\Models\PayoutRecipient;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLedgerEntry;
use Database\Seeders\LocalTestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalTestingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_test_users_and_roles(): void
    {
        $this->seed(LocalTestingSeeder::class);

        foreach ([
            'admin@test.local',
            'live@test.local',
            'sandbox@test.local',
            'pending@test.local',
            'suspended@test.local',
        ] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }

        $this->assertTrue(User::where('email', 'admin@test.local')->first()->hasRole('admin'));
        $this->assertTrue(User::where('email', 'live@test.local')->first()->hasRole('merchant'));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(LocalTestingSeeder::class);
        $this->seed(LocalTestingSeeder::class);

        $this->assertSame(1, User::where('email', 'live@test.local')->count());
        $this->assertSame(1, Merchant::where('public_id', 'mer_local_live')->count());
        $this->assertSame(1, ApiKey::where('publishable_key', 'pay_pk_live_sandbox')->count());
        $this->assertSame(1, PaymentLink::where('public_id', 'plink_local_live')->count());
        $this->assertSame(1, Invoice::where('public_id', 'inv_local_live')->count());
    }

    public function test_verified_merchant_has_wallet_api_keys_and_sample_data(): void
    {
        $this->seed(LocalTestingSeeder::class);

        $merchant = Merchant::where('public_id', 'mer_local_live')->firstOrFail();

        $this->assertSame('active', $merchant->status);
        $this->assertSame('verified', $merchant->compliance_status);
        $this->assertTrue($merchant->live_enabled);
        $this->assertSame(10000, $merchant->wallet->available_balance);
        $this->assertSame(2, $merchant->apiKeys()->count());
        $this->assertSame(3, $merchant->transactions()->count());
        $this->assertTrue($merchant->paymentLinks()->exists());
        $this->assertTrue($merchant->invoices()->exists());
        $this->assertTrue($merchant->payoutRecipients()->exists());
        $this->assertTrue($merchant->payouts()->exists());
        $this->assertTrue($merchant->webhookEndpoint()->exists());
        $this->assertSame(2, WalletLedgerEntry::where('merchant_id', $merchant->id)->count());
    }

    public function test_sandbox_merchant_has_sandbox_only_key_and_sample_data(): void
    {
        $this->seed(LocalTestingSeeder::class);

        $merchant = Merchant::where('public_id', 'mer_local_sandbox')->firstOrFail();

        $this->assertFalse($merchant->live_enabled);
        $this->assertSame(['sandbox'], $merchant->apiKeys()->pluck('environment')->all());
        $this->assertTrue(PaymentLink::where('merchant_id', $merchant->id)->exists());
        $this->assertTrue(Invoice::where('merchant_id', $merchant->id)->exists());
    }
}
