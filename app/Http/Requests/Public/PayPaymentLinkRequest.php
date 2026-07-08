<?php

namespace App\Http\Requests\Public;

use App\Models\PaymentLink;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class PayPaymentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'amount' => ['nullable', 'numeric'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $paymentLink = PaymentLink::where('slug', (string) $this->route('slug'))->first();

            if (! $paymentLink?->allow_custom_amount) {
                return;
            }

            $amount = $this->input('amount');

            if ($amount === null || $amount === '') {
                $validator->errors()->add('amount', 'Amount is required.');
                return;
            }

            if (! Money::isAtLeastMinimum($amount)) {
                $validator->errors()->add('amount', 'Amount must be at least '.Money::minimumAmount().'.');
            }
        });
    }
}
