<?php

namespace Tests\Unit;

use App\Services\Mpesa\CallbackParser;
use Tests\TestCase;

class CallbackParserTest extends TestCase
{
    public function test_parses_stk_callback_payload(): void
    {
        $parser = new CallbackParser();
        $payload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_123',
                    'MerchantRequestID' => '12345',
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CustomerMessage' => 'Success. Request accepted for processing',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 100],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'ABC123'],
                        ],
                    ],
                ],
            ],
        ];

        $result = $parser->parse($payload);

        $this->assertSame('ws_CO_123', $result['checkout_request_id']);
        $this->assertSame('12345', $result['merchant_request_id']);
        $this->assertSame('0', $result['result_code']);
        $this->assertSame('Success', $result['result_description']);
        $this->assertSame('Success. Request accepted for processing', $result['customer_message']);
        $this->assertSame(100, $result['callback_metadata']['Amount']);
        $this->assertSame('ABC123', $result['callback_metadata']['MpesaReceiptNumber']);
    }
}
