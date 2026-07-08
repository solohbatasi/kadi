<?php

namespace Tests\Feature;

use App\Services\Mpesa\DarajaAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DarajaAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_access_token_uses_sandbox_credentials(): void
    {
        config(['mpesa.consumer_key' => 'test-key']);
        config(['mpesa.consumer_secret' => 'test-secret']);
        config(['mpesa.timeout' => 30]);

        Http::fake([
            'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'test-token'], 200),
        ]);

        $service = app(DarajaAuthService::class);
        $token = $service->getAccessToken('sandbox');

        $this->assertSame('test-token', $token);
    }
}
