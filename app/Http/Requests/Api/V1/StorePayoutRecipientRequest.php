<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Developer\StorePayoutRecipientRequest as DeveloperStorePayoutRecipientRequest;

class StorePayoutRecipientRequest extends DeveloperStorePayoutRecipientRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
    }
}
