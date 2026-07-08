<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class PaymentRateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('payments-api-read', fn (Request $request) => $this->apiLimit($request, 120));
        RateLimiter::for('payments-api-write', fn (Request $request) => $this->apiLimit($request, 60));
        RateLimiter::for('payments-api-stk', fn (Request $request) => $this->apiLimit($request, 30));
        RateLimiter::for('payments-api-payouts', fn (Request $request) => $this->apiLimit($request, 20));

        RateLimiter::for('public-payment-submit', fn (Request $request) => Limit::perMinute(10)
            ->by('public-pay:'.$request->ip())
            ->response(fn () => response('Too many payment attempts. Please wait and try again.', Response::HTTP_TOO_MANY_REQUESTS)));

        RateLimiter::for('mpesa-callbacks', fn (Request $request) => Limit::perMinute(240)
            ->by('mpesa-callback:'.$request->ip())
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many callback requests.',
                'data' => null,
                'errors' => ['rate_limit' => ['Please retry later.']],
            ], Response::HTTP_TOO_MANY_REQUESTS)));

        RateLimiter::for('dashboard-sensitive-mutation', fn (Request $request) => Limit::perMinute(12)
            ->by('dashboard:'.($request->user()?->id ?: $request->ip())));
    }

    protected function apiLimit(Request $request, int $maxAttempts): Limit
    {
        $apiKey = $request->attributes->get('apiKey');
        $merchant = $request->attributes->get('merchant');
        $key = $apiKey?->id ? 'api-key:'.$apiKey->id : 'merchant:'.($merchant?->id ?: $request->ip());

        return Limit::perMinute($maxAttempts)
            ->by($key)
            ->response(fn () => response()->json([
                'success' => false,
                'message' => 'Too many requests.',
                'data' => null,
                'errors' => ['rate_limit' => ['Please retry later.']],
            ], Response::HTTP_TOO_MANY_REQUESTS));
    }
}

