<?php

namespace App\Http\Requests\Developer;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class SubmitComplianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_email' => ['required', 'email', 'max:255'],
            'business_phone' => ['required', 'string', 'max:20'],
            'business_type' => ['required', 'string', 'max:100'],
            'platform_url' => ['required', 'url', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_phone' => ['required', 'string', 'max:20'],
            'document_type' => ['required', 'string', 'max:100'],
            'document_number' => ['required', 'string', 'max:100'],
            'kra_pin' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'payout_phone' => ['nullable', 'string', 'max:20'],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (['business_phone', 'owner_phone', 'payout_phone'] as $field) {
                $value = $this->input($field);

                if ($value && strlen(PhoneNumber::normalize($value)) !== 12) {
                    $validator->errors()->add($field, 'Enter a valid Kenyan phone number.');
                }
            }
        });
    }
}

