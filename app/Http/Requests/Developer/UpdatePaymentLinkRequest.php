<?php

namespace App\Http\Requests\Developer;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'allow_custom_amount' => ['nullable', 'boolean'],
            'success_redirect_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allowCustomAmount = $this->boolean('allow_custom_amount');
            $amount = $this->input('amount');

            if (! $allowCustomAmount && ($amount === null || $amount === '')) {
                $validator->errors()->add('amount', 'Amount is required for fixed amount payment links.');
            }

            if ($amount !== null && $amount !== '' && ! Money::isAtLeastMinimum($amount)) {
                $validator->errors()->add('amount', 'Amount must be at least '.Money::minimumAmount().'.');
            }
        });
    }
}
