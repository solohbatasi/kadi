<?php

namespace App\Http\Requests\Developer;

use Illuminate\Foundation\Http\FormRequest;

class StorePayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'phone' => ['nullable', 'string', 'max:30'],
            'recipient_public_id' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->input('phone') && ! $this->input('recipient_public_id')) {
                $validator->errors()->add('phone', 'A phone number or recipient is required.');
            }
        });
    }
}
