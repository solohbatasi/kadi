<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Developer\UpdatePaymentLinkRequest as DeveloperUpdatePaymentLinkRequest;

class UpdatePaymentLinkRequest extends DeveloperUpdatePaymentLinkRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
    }
}
