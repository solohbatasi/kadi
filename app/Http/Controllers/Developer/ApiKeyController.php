<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\CreateApiKeyRequest;
use App\Models\ApiKey;
use App\Services\Payments\ApiKeyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function __construct(protected ApiKeyService $apiKeyService)
    {
    }

    public function index(): Response
    {
        $merchant = auth()->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $apiKeys = $merchant->apiKeys()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'environment', 'publishable_key', 'status', 'created_at', 'last_used_at', 'revoked_at']);

        return Inertia::render('Developer/ApiKeys/Index', [
            'apiKeys' => $apiKeys,
        ]);
    }

    public function store(CreateApiKeyRequest $request): RedirectResponse
    {
        $merchant = auth()->user()->merchant;

        if (! $merchant) {
            abort(404, 'Merchant not found.');
        }

        $validated = $request->validated();
        $result = $this->apiKeyService->createKey($merchant, $validated['name'], $validated['environment']);

        return back()->with('flash.banner', 'API key created. Store the secret once.')
            ->with('api_key_secret', $result['secret']);
    }

    public function revoke(ApiKey $apiKey): RedirectResponse
    {
        $this->authorizeKeyOwnership($apiKey);
        $this->apiKeyService->revokeKey($apiKey);

        return back()->with('flash.banner', 'API key revoked.');
    }

    public function rotate(ApiKey $apiKey): RedirectResponse
    {
        $this->authorizeKeyOwnership($apiKey);
        $newSecret = $this->apiKeyService->rotateKey($apiKey);

        return back()->with('flash.banner', 'API key rotated. Store the secret once.')
            ->with('api_key_secret', $newSecret);
    }

    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        $this->authorizeKeyOwnership($apiKey);
        $this->apiKeyService->deleteKey($apiKey);

        return back()->with('flash.banner', 'API key deleted.');
    }

    protected function authorizeKeyOwnership(ApiKey $apiKey): void
    {
        $merchant = request()->user()->merchant;

        if (! $merchant || $apiKey->merchant_id !== $merchant->id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
