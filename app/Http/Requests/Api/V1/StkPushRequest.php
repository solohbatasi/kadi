<?php

namespace App\Http\Requests\Api\V1;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class StkPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'min:9', 'max:15'],
            'amount' => ['required', 'numeric', 'min:' . config('payments.min_stk_amount', 10)],
            'reference' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1024'],
        ];
    }

    public function validatedPhone(): string
    {
        return PhoneNumber::normalize($this->input('phone'));
    }

    public function validatedAmount(): int
    {
        return (int) round($this->input('amount'));
    }
}
