<?php

return [
    'environment' => env('MPESA_ENV', 'sandbox'),
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),
    'callback_url' => env('MPESA_CALLBACK_URL'),
    'callback_secret' => env('MPESA_CALLBACK_SECRET'),
    'timeout' => env('MPESA_TIMEOUT', 30),
    'b2c' => [
        'initiator_name' => env('MPESA_B2C_INITIATOR_NAME'),
        'security_credential' => env('MPESA_B2C_SECURITY_CREDENTIAL'),
        'command_id' => env('MPESA_B2C_COMMAND_ID', 'BusinessPayment'),
        'result_url' => env('MPESA_B2C_RESULT_URL'),
        'timeout_url' => env('MPESA_B2C_TIMEOUT_URL'),
        'queue_timeout_url' => env('MPESA_B2C_QUEUE_TIMEOUT_URL'),
        'fake' => (bool) env('MPESA_B2C_FAKE', true),
        'fake_result' => env('MPESA_B2C_FAKE_RESULT', 'success'),
    ],
];
