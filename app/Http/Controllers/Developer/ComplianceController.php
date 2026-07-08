<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\SubmitComplianceRequest;
use App\Models\AuditLog;
use App\Models\Merchant;
use App\Services\Payments\MerchantBootstrapService;
use App\Support\Mask;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function __construct(protected MerchantBootstrapService $bootstrap)
    {
    }

    public function edit(Request $request): Response
    {
        $merchant = $this->bootstrap->bootstrapForUser($request->user());
        $profile = $this->bootstrap->ensureProfile($merchant);

        return Inertia::render('Developer/Compliance/Edit', [
            'merchant' => [
                'business_name' => $merchant->business_name,
                'business_email' => $merchant->business_email,
                'business_phone' => Mask::phone($merchant->business_phone),
                'business_type' => $merchant->business_type,
                'platform_url' => $merchant->platform_url,
                'compliance_status' => $merchant->compliance_status,
            ],
            'profile' => [
                'owner_name' => $profile->owner_name,
                'owner_email' => $profile->owner_email,
                'owner_phone' => Mask::phone($profile->owner_phone),
                'document_type' => $profile->document_type,
                'document_number_set' => filled($profile->document_number),
                'kra_pin_set' => filled($profile->kra_pin),
                'address' => $profile->address,
                'terms_accepted_at' => $profile->terms_accepted_at,
                'privacy_accepted_at' => $profile->privacy_accepted_at,
            ],
        ]);
    }

    public function submit(SubmitComplianceRequest $request): RedirectResponse
    {
        $merchant = $this->bootstrap->bootstrapForUser($request->user());
        $profile = $this->bootstrap->ensureProfile($merchant);
        $validated = $request->validated();

        $merchant->update([
            'business_name' => $validated['business_name'],
            'business_email' => $validated['business_email'],
            'business_phone' => PhoneNumber::normalize($validated['payout_phone'] ?: $validated['business_phone']),
            'business_type' => $validated['business_type'],
            'platform_url' => $validated['platform_url'],
            'compliance_status' => 'pending_review',
        ]);

        $profile->update([
            'owner_name' => $validated['owner_name'],
            'owner_email' => $validated['owner_email'],
            'owner_phone' => PhoneNumber::normalize($validated['owner_phone']),
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'],
            'kra_pin' => $validated['kra_pin'],
            'address' => $validated['address'],
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
        ]);

        AuditLog::create([
            'merchant_id' => $merchant->id,
            'user_id' => $request->user()->id,
            'action' => 'merchant.compliance_submitted',
            'subject_type' => Merchant::class,
            'subject_id' => $merchant->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['merchant_public_id' => $merchant->public_id],
            'created_at' => now(),
        ]);

        return redirect()->route('developer.onboarding.index')
            ->with('flash.banner', 'Compliance information submitted for review.');
    }
}

