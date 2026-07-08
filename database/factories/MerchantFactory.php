<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Merchant>
 */
class MerchantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'public_id' => 'mer_'.Str::random(24),
            'business_name' => fake()->company(),
            'business_email' => fake()->safeEmail(),
            'status' => 'active',
            'compliance_status' => 'incomplete',
            'live_enabled' => true,
        ];
    }
}
