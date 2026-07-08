<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Developer\StoreInvoiceRequest as DeveloperStoreInvoiceRequest;

class StoreInvoiceRequest extends DeveloperStoreInvoiceRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
    }
}
