<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Services\Payments\MerchantBootstrapService;
use App\Services\Payments\MerchantReadinessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        protected MerchantBootstrapService $bootstrap,
        protected MerchantReadinessService $readiness
    ) {
    }

    public function index(Request $request): Response
    {
        $merchant = $this->bootstrap->bootstrapForUser($request->user());
        $this->bootstrap->ensureProfile($merchant);
        $this->bootstrap->ensureWallet($merchant);
        $this->bootstrap->ensureWebhookEndpoint($merchant);

        return Inertia::render('Developer/Onboarding/Index', [
            'merchant' => $merchant->fresh(['profile']),
            'readiness' => $this->readiness->checklist($merchant->fresh()),
        ]);
    }
}

