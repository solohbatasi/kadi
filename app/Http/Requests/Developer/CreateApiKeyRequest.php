<?php

namespace App\Http\Requests\Developer;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'environment' => ['required', 'in:sandbox,production'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('environment') !== 'production') {
                return;
            }

            $merchant = $this->user()?->merchant;

            if (! $merchant || ! $merchant->live_enabled) {
                $validator->errors()->add('environment', 'Production keys require your merchant account to be live enabled.');
            }
        });
    }
}
