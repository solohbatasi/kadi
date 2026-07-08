<?php

namespace App\Http\Requests\Developer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'url' => ['nullable', 'url', 'max:2048'],
            'secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'is_enabled' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->boolean('is_enabled')) {
                return;
            }

            $endpoint = $this->user()?->merchant?->webhookEndpoint;

            if (! $this->input('url') && ! $endpoint?->url) {
                $validator->errors()->add('url', 'A webhook URL is required before enabling webhooks.');
            }

            if (! $this->input('secret') && ! $endpoint?->secret) {
                $validator->errors()->add('secret', 'A webhook secret is required before enabling webhooks.');
            }
        });
    }
}
