<?php

namespace App\Services\Payments;

use App\Models\Merchant;

class MerchantReadinessService
{
    public function checklist(Merchant $merchant): array
    {
        $merchant->loadMissing(['profile', 'webhookEndpoint', 'payoutRecipients', 'apiKeys', 'transactions']);

        $items = [
            'business_profile_completed' => $this->businessProfileCompleted($merchant),
            'contact_information_completed' => $this->contactInformationCompleted($merchant),
            'payout_configured' => $this->payoutConfigured($merchant),
            'webhook_configured' => (bool) ($merchant->webhookEndpoint?->is_enabled && $merchant->webhookEndpoint?->url && $merchant->webhookEndpoint?->secret),
            'sandbox_api_key_created' => $merchant->apiKeys->contains(fn ($key) => $key->environment === 'sandbox' && $key->status === 'active'),
            'first_sandbox_transaction_attempted' => $merchant->transactions->contains(fn ($transaction) => $transaction->environment === 'sandbox'),
            'compliance_submitted' => in_array($merchant->compliance_status, ['pending_review', 'verified', 'rejected'], true),
            'compliance_verified' => $merchant->compliance_status === 'verified',
            'live_mode_enabled' => (bool) $merchant->live_enabled,
            'terms_accepted' => (bool) ($merchant->profile?->terms_accepted_at && $merchant->profile?->privacy_accepted_at),
        ];

        return [
            'items' => $items,
            'completed' => collect($items)->filter()->count(),
            'total' => count($items),
            'percent' => (int) round((collect($items)->filter()->count() / count($items)) * 100),
            'can_request_live' => $this->canRequestLive($merchant),
            'blocking_reasons' => $this->blockingReasons($merchant),
        ];
    }

    public function canRequestLive(Merchant $merchant): bool
    {
        $merchant->loadMissing(['profile', 'apiKeys']);

        return $this->businessProfileCompleted($merchant)
            && $this->contactInformationCompleted($merchant)
            && $this->termsAccepted($merchant)
            && $merchant->apiKeys()->where('environment', 'sandbox')->where('status', 'active')->exists()
            && in_array($merchant->compliance_status, ['pending_review', 'verified'], true);
    }

    public function blockingReasons(Merchant $merchant): array
    {
        $merchant->loadMissing(['profile', 'apiKeys']);
        $reasons = [];

        if (! $this->businessProfileCompleted($merchant)) {
            $reasons[] = 'Complete your business profile.';
        }

        if (! $this->contactInformationCompleted($merchant)) {
            $reasons[] = 'Complete business and owner contact information.';
        }

        if (! $this->termsAccepted($merchant)) {
            $reasons[] = 'Accept the terms and privacy policy.';
        }

        if (! $merchant->apiKeys()->where('environment', 'sandbox')->where('status', 'active')->exists()) {
            $reasons[] = 'Create a sandbox API key.';
        }

        if (! in_array($merchant->compliance_status, ['pending_review', 'verified'], true)) {
            $reasons[] = 'Submit compliance information for review.';
        }

        return $reasons;
    }

    protected function businessProfileCompleted(Merchant $merchant): bool
    {
        return filled($merchant->business_name)
            && filled($merchant->business_email)
            && filled($merchant->business_phone)
            && filled($merchant->business_type)
            && filled($merchant->platform_url);
    }

    protected function contactInformationCompleted(Merchant $merchant): bool
    {
        return filled($merchant->profile?->owner_name)
            && filled($merchant->profile?->owner_email)
            && filled($merchant->profile?->owner_phone);
    }

    protected function payoutConfigured(Merchant $merchant): bool
    {
        return filled($merchant->business_phone)
            || $merchant->payoutRecipients->contains(fn ($recipient) => $recipient->status === 'active');
    }

    protected function termsAccepted(Merchant $merchant): bool
    {
        return (bool) ($merchant->profile?->terms_accepted_at && $merchant->profile?->privacy_accepted_at);
    }
}
