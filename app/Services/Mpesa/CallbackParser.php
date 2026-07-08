<?php

namespace App\Services\Mpesa;

use RuntimeException;

class CallbackParser
{
    public function parse(array $payload): array
    {
        $stkCallback = $payload['Body']['stkCallback'] ?? null;

        if (! is_array($stkCallback)) {
            throw new RuntimeException('Invalid M-Pesa callback payload.');
        }

        $metadata = $stkCallback['CallbackMetadata']['Item'] ?? [];

        return [
            'checkout_request_id' => $stkCallback['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $stkCallback['MerchantRequestID'] ?? null,
            'result_code' => isset($stkCallback['ResultCode']) ? (string) $stkCallback['ResultCode'] : null,
            'result_description' => $stkCallback['ResultDesc'] ?? null,
            'customer_message' => $stkCallback['CustomerMessage'] ?? null,
            'callback_metadata' => $this->normalizeCallbackMetadata($metadata),
        ];
    }

    protected function normalizeCallbackMetadata(array $items): array
    {
        return array_reduce($items, function (array $carry, array $item) {
            $name = $item['Name'] ?? null;

            if ($name === null) {
                return $carry;
            }

            $carry[$name] = $item['Value'] ?? null;

            return $carry;
        }, []);
    }
}
