<?php

return [
    'platform_name' => env('PAYMENT_PLATFORM_NAME', config('app.name')),
    'default_commission_type' => env('PAYMENT_DEFAULT_COMMISSION_TYPE', 'percentage'),
    'default_commission_percent' => (float) env('PAYMENT_DEFAULT_COMMISSION_PERCENT', 2.0),
    'default_commission_flat' => (int) env('PAYMENT_DEFAULT_COMMISSION_FLAT', 0),
    'min_stk_amount' => (int) env('PAYMENT_MIN_STK_AMOUNT', 10),
    'min_payout_amount' => (int) env('PAYMENT_MIN_PAYOUT_AMOUNT', 10),
    'pending_transaction_timeout_minutes' => (int) env('PAYMENT_PENDING_TRANSACTION_TIMEOUT_MINUTES', 15),
    'idempotency_key_retention_days' => (int) env('PAYMENT_IDEMPOTENCY_KEY_RETENTION_DAYS', 1),
    'allow_sandbox_payouts_without_verified_compliance' => (bool) env('PAYMENT_ALLOW_SANDBOX_PAYOUTS_WITHOUT_VERIFIED_COMPLIANCE', true),
    'currency' => env('PAYMENT_DEFAULT_CURRENCY', 'KES'),
];
