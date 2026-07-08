<?php

namespace Tests\Unit;

use App\Models\Merchant;
use App\Models\CommissionRule;
use App\Services\Payments\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_percentage_commission_from_merchant_rule(): void
    {
        $merchant = Merchant::factory()->create();
        CommissionRule::create([
            'merchant_id' => $merchant->id,
            'name' => 'Default',
            'type' => 'percentage',
            'percentage' => 2.5,
            'flat_amount' => 0,
            'is_default' => true,
            'is_active' => true,
        ]);

        $service = app(CommissionService::class);

        $this->assertSame(3, $service->calculate($merchant, 120));
    }

    public function test_returns_default_commission_when_no_rule_exists(): void
    {
        config(['payments.default_commission_type' => 'percentage']);
        config(['payments.default_commission_percent' => 2.0]);

        $merchant = Merchant::factory()->create();
        $service = app(CommissionService::class);

        $this->assertSame(2, $service->calculate($merchant, 100));
    }
}
