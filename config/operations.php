<?php

return [
    'alert_email' => env('OPERATIONS_ALERT_EMAIL'),
    'failed_webhook_threshold' => (int) env('OPERATIONS_ALERT_FAILED_WEBHOOK_THRESHOLD', 10),
    'pending_payout_threshold' => (int) env('OPERATIONS_ALERT_PENDING_PAYOUT_THRESHOLD', 10),
    'failed_payout_threshold' => (int) env('OPERATIONS_ALERT_FAILED_PAYOUT_THRESHOLD', 10),
    'stale_transaction_threshold' => (int) env('OPERATIONS_ALERT_STALE_TRANSACTION_THRESHOLD', 20),
];

