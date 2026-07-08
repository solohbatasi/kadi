<?php

namespace App\Services\Payments;

use App\Models\Merchant;

class CommissionService
{
    public function calculate(Merchant $merchant, int $amount): int
    {
        $rule = $merchant->commissionRules()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();

        if ($rule) {
            return $this->calculateFromRule($rule, $amount);
        }

        return $this->calculateDefault($amount);
    }

    protected function calculateFromRule($rule, int $amount): int
    {
        if ($rule->type === 'flat') {
            $fee = $rule->flat_amount;
        } else {
            $fee = (int) ceil($amount * ($rule->percentage / 100));
        }

        if ($rule->minimum_fee !== null) {
            $fee = max($fee, $rule->minimum_fee);
        }

        if ($rule->maximum_fee !== null) {
            $fee = min($fee, $rule->maximum_fee);
        }

        return max(0, $fee);
    }

    protected function calculateDefault(int $amount): int
    {
        $type = config('payments.default_commission_type', 'percentage');
        $percent = (float) config('payments.default_commission_percent', 2.0);
        $flat = (int) config('payments.default_commission_flat', 0);
        $minimum = (int) config('payments.default_commission_flat', 0);

        if ($type === 'flat') {
            return max(0, $flat);
        }

        $fee = (int) ceil($amount * ($percent / 100));

        return max(0, $fee);
    }
}
