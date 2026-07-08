<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Merchant;
use App\Services\Payments\MerchantReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LiveModeController extends Controller
{
    public function __construct(protected MerchantReadinessService $readiness)
    {
    }

    public function request(Request $request): RedirectResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        if (! $this->readiness->canRequestLive($merchant)) {
            return back()->withErrors(['live_mode' => 'Complete the onboarding checklist before requesting live mode.']);
        }

        $merchant->update([
            'live_requested_at' => now(),
            'live_reviewed_at' => null,
            'live_rejection_reason' => null,
        ]);

        AuditLog::create([
            'merchant_id' => $merchant->id,
            'user_id' => $request->user()->id,
            'action' => 'merchant.live_requested',
            'subject_type' => Merchant::class,
            'subject_id' => $merchant->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['merchant_public_id' => $merchant->public_id],
            'created_at' => now(),
        ]);

        return back()->with('flash.banner', 'Live mode request submitted.');
    }
}

