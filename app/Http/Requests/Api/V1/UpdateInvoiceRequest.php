<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Developer\UpdateInvoiceRequest as DeveloperUpdateInvoiceRequest;

class UpdateInvoiceRequest extends DeveloperUpdateInvoiceRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
    }
}
