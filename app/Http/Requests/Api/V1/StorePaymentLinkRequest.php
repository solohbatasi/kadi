<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Developer\StorePaymentLinkRequest as DeveloperStorePaymentLinkRequest;

class StorePaymentLinkRequest extends DeveloperStorePaymentLinkRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
    }
}
