<?php

return [
    'platform_name' => env('PAYMENT_PLATFORM_NAME', config('app.name')),
    'default_commission_type' => env('PAYMENT_DEFAULT_COMMISSION_TYPE', 'percentage'),
    'default_commission_percent' => (float) env('PAYMENT_DEFAULT_COMMISSION_PERCENT', 2.0),
    'default_commission_flat' => (int) env('PAYMENT_DEFAULT_COMMISSION_FLAT', 0),
    'min_stk_amount' => (int) env('PAYMENT_MIN_STK_AMOUNT', 10),
    'currency' => env('PAYMENT_DEFAULT_CURRENCY', 'KES'),
];
