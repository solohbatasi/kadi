<?php

namespace App\Services\Payments;

use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Merchant;
use Illuminate\Support\Facades\Hash;

class ApiKeyService
{
    private const SECRET_PREFIX_LENGTH = 10;

    public function createKey(Merchant $merchant, string $name, string $environment = 'sandbox'): array
    {
        $secret = $this->generateSecretKey();

        $apiKey = ApiKey::create([
            'merchant_id' => $merchant->id,
            'name' => $name,
            'environment' => $environment,
            'publishable_key' => $this->generatePublishableKey(),
            'secret_key_hash' => Hash::make($secret),
            'secret_key_prefix' => $this->getSecretKeyPrefix($secret),
            'secret_key_last_four' => substr($secret, -4),
        ]);

        $this->logAction($apiKey, 'api_key.created', [
            'name' => $name,
            'environment' => $environment,
        ]);

        return [
            'apiKey' => $apiKey,
            'secret' => $secret,
        ];
    }

    public function revokeKey(ApiKey $apiKey): ApiKey
    {
        $apiKey->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $this->logAction($apiKey, 'api_key.revoked');

        return $apiKey;
    }

    public function rotateKey(ApiKey $apiKey): string
    {
        $secret = $this->generateSecretKey();

        $apiKey->update([
            'secret_key_hash' => Hash::make($secret),
            'secret_key_prefix' => $this->getSecretKeyPrefix($secret),
            'secret_key_last_four' => substr($secret, -4),
            'last_used_at' => null,
            'revoked_at' => null,
            'status' => 'active',
        ]);

        $this->logAction($apiKey, 'api_key.rotated');

        return $secret;
    }

    public function deleteKey(ApiKey $apiKey): bool
    {
        $this->logAction($apiKey, 'api_key.deleted');

        return $apiKey->delete();
    }

    public function resolveSecretKey(string $secret): ?ApiKey
    {
        if (! str_starts_with($secret, 'pay_sk_') || strlen($secret) < self::SECRET_PREFIX_LENGTH) {
            return null;
        }

        $prefix = substr($secret, 0, self::SECRET_PREFIX_LENGTH);
        $lastFour = substr($secret, -4);

        $apiKey = ApiKey::where('status', 'active')
            ->where('secret_key_prefix', $prefix)
            ->where('secret_key_last_four', $lastFour)
            ->get()
            ->first(fn (ApiKey $apiKey) => Hash::check($secret, $apiKey->secret_key_hash));

        if (! $apiKey) {
            return null;
        }

        $apiKey->update(['last_used_at' => now()]);

        return $apiKey;
    }

    protected function generateSecretKey(): string
    {
        return 'pay_sk_'.bin2hex(random_bytes(32));
    }

    protected function generatePublishableKey(): string
    {
        return 'pay_pk_'.bin2hex(random_bytes(24));
    }

    protected function getSecretKeyPrefix(string $secret): string
    {
        return substr($secret, 0, self::SECRET_PREFIX_LENGTH);
    }

    protected function logAction(ApiKey $apiKey, string $action, array $metadata = []): void
    {
        AuditLog::create([
            'merchant_id' => $apiKey->merchant_id,
            'user_id' => request()?->user()?->id,
            'action' => $action,
            'subject_type' => ApiKey::class,
            'subject_id' => $apiKey->id,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
