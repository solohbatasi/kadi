<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Developer\StorePayoutRequest as DeveloperStorePayoutRequest;

class StorePayoutRequest extends DeveloperStorePayoutRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
    }
}
