<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payments\PaymentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_fixed_amount_payment_link(): void
    {
        $merchant = $this->merchant();

        $paymentLink = app(PaymentLinkService::class)->create($merchant, [
            'title' => 'School Fees',
            'amount' => 500,
            'allow_custom_amount' => false,
        ]);

        $this->assertStringStartsWith('plink_', $paymentLink->public_id);
        $this->assertSame('school-fees', $paymentLink->slug);
        $this->assertSame(500, $paymentLink->amount);
        $this->assertFalse($paymentLink->allow_custom_amount);
    }

    public function test_creates_custom_amount_payment_link(): void
    {
        $paymentLink = app(PaymentLinkService::class)->create($this->merchant(), [
            'title' => 'Donation',
            'allow_custom_amount' => true,
        ]);

        $this->assertNull($paymentLink->amount);
        $this->assertTrue($paymentLink->allow_custom_amount);
    }

    public function test_slug_is_unique(): void
    {
        $merchant = $this->merchant();
        $service = app(PaymentLinkService::class);

        $first = $service->create($merchant, ['title' => 'Checkout', 'amount' => 100]);
        $second = $service->create($merchant, ['title' => 'Checkout', 'amount' => 200]);

        $this->assertSame('checkout', $first->slug);
        $this->assertSame('checkout-2', $second->slug);
    }

    public function test_fixed_amount_link_requires_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PaymentLinkService::class)->create($this->merchant(), [
            'title' => 'Fixed',
            'allow_custom_amount' => false,
        ]);
    }

    public function test_custom_amount_link_allows_null_amount(): void
    {
        $paymentLink = app(PaymentLinkService::class)->create($this->merchant(), [
            'title' => 'Custom',
            'allow_custom_amount' => true,
            'amount' => null,
        ]);

        $this->assertNull($paymentLink->amount);
    }

    public function test_inactive_link_cannot_be_paid(): void
    {
        $paymentLink = app(PaymentLinkService::class)->create($this->merchant(), [
            'title' => 'Inactive',
            'amount' => 100,
            'status' => 'inactive',
        ]);

        $this->assertNull(app(PaymentLinkService::class)->findActiveBySlug($paymentLink->slug));
    }

    protected function merchant()
    {
        $user = User::factory()->withPersonalTeam()->create();

        return $user->merchant()->create([
            'public_id' => 'mer_'.str()->random(16),
            'business_name' => 'Test Merchant',
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ]);
    }
}
