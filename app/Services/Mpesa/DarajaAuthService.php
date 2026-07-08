<?php

namespace App\Services\Mpesa;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DarajaAuthService
{
    public function getAccessToken(string $environment): string
    {
        $config = config('mpesa');

        if (empty($config['consumer_key']) || empty($config['consumer_secret'])) {
            throw new RuntimeException('M-Pesa credentials are not configured.');
        }

        return Cache::remember(sprintf('mpesa_oauth_token:%s', $environment), 55 * 60, function () use ($config, $environment) {
            $url = $this->buildBaseUrl($environment).'/oauth/v1/generate?grant_type=client_credentials';

            $response = Http::withBasicAuth($config['consumer_key'], $config['consumer_secret'])
                ->acceptJson()
                ->timeout($config['timeout'] ?? 30)
                ->get($url);

            if (! $response->successful() || empty($response->json('access_token'))) {
                throw new RuntimeException('Unable to fetch M-Pesa access token.');
            }

            return (string) $response->json('access_token');
        });
    }

    protected function buildBaseUrl(string $environment): string
    {
        return $environment === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }
}
