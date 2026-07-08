<?php

namespace App\Http\Middleware;

use App\Services\Payments\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticateApiKey
{
    public function __construct(protected ApiKeyService $apiKeyService)
    {
    }

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $apiKeyHeader = $request->header('x-api-key');
        $authorization = $request->header('authorization');
        $apiKey = $this->resolveApiKeyFromHeaders($apiKeyHeader, $authorization);

        if (empty($apiKey)) {
            return $this->unauthorized('API key is required.');
        }

        if (! str_starts_with($apiKey, 'pay_sk_')) {
            return $this->unauthorized('Secret API key required.');
        }

        $key = $this->apiKeyService->resolveSecretKey($apiKey);

        if (! $key) {
            return $this->unauthorized('Invalid API key.');
        }

        if (! $key->isActive()) {
            return $this->unauthorized('Invalid API key.');
        }

        if (! $key->merchant || $key->merchant->status !== 'active') {
            return $this->forbidden('Merchant account is not active.');
        }

        if ($key->environment === 'production' && ! $key->merchant->live_enabled) {
            return $this->forbidden('Production API access requires live mode.');
        }

        $request->attributes->set('apiKey', $key);
        $request->attributes->set('merchant', $key->merchant);
        $request->attributes->set('apiEnvironment', $key->environment);

        return $next($request);
    }

    protected function resolveApiKeyFromHeaders(?string $apiKeyHeader, ?string $authorization): string
    {
        if (! empty($apiKeyHeader)) {
            return trim($apiKeyHeader);
        }

        if (! empty($authorization)) {
            if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
                return trim($matches[1]);
            }

            if (preg_match('/^ApiKey\s+(.+)$/i', $authorization, $matches)) {
                return trim($matches[1]);
            }
        }

        return '';
    }

    protected function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], SymfonyResponse::HTTP_UNAUTHORIZED);
    }

    protected function forbidden(string $message): JsonResponse
    {
        return response()->json(['message' => $message], SymfonyResponse::HTTP_FORBIDDEN);
    }
}
