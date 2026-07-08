<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Services\Payments\MerchantBootstrapService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeveloperDashboardController extends Controller
{
    public function __construct(protected MerchantBootstrapService $bootstrapService)
    {
    }

    public function index(Request $request): Response
    {
        $merchant = $this->bootstrapService->bootstrapForUser($request->user());
        $this->bootstrapService->ensureProfile($merchant);
        $this->bootstrapService->ensureWallet($merchant);
        $this->bootstrapService->ensureWebhookEndpoint($merchant);

        return Inertia::render('Developer/Dashboard', [
            'merchant' => [
                'id' => $merchant->id,
                'public_id' => $merchant->public_id,
                'business_name' => $merchant->business_name,
                'business_email' => $merchant->business_email,
                'status' => $merchant->status,
                'compliance_status' => $merchant->compliance_status,
                'live_enabled' => $merchant->live_enabled,
            ],
        ]);
    }
}
