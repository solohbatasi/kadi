<?php

namespace App\Services\Operations;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class PreLiveCheckService
{
    public function results(): array
    {
        return [
            'application' => $this->applicationChecks(),
            'database' => $this->databaseChecks(),
            'mpesa' => $this->mpesaChecks(),
            'security' => $this->securityChecks(),
            'operations' => $this->operationsChecks(),
            'admin' => $this->adminChecks(),
            'money_safety' => $this->moneySafetyChecks(),
        ];
    }

    public function hasCriticalFailures(array $results): bool
    {
        return collect($results)
            ->flatten(1)
            ->contains(fn ($check) => ($check['status'] ?? null) === 'FAIL');
    }

    protected function applicationChecks(): array
    {
        $production = app()->environment('production');

        return [
            $this->check(true, 'APP_ENV is '.app()->environment(), false),
            $this->check(! ($production && config('app.debug')), 'APP_DEBUG is safe for environment', $production),
            $this->check(filled(config('app.key')), 'APP_KEY is set', true),
            $this->check(! ($production && ! str_starts_with((string) config('app.url'), 'https://')), 'APP_URL uses HTTPS when production', $production),
            $this->check(filled(config('app.timezone')), 'Timezone is set', false),
            $this->check(! ($production && config('queue.default') === 'sync'), 'Queue connection is not sync in production', $production),
            $this->check(config('cache.default') !== null, 'Cache store is configured', false),
            $this->check(config('session.driver') !== 'array', 'Session driver is not array', false),
        ];
    }

    protected function databaseChecks(): array
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks[] = $this->check(true, 'Database connection works', true);
        } catch (\Throwable) {
            $checks[] = $this->check(false, 'Database connection works', true);
        }

        $requiredTables = [
            'merchants',
            'api_keys',
            'wallets',
            'wallet_ledger_entries',
            'transactions',
            'mpesa_callbacks',
            'payment_links',
            'invoices',
            'payouts',
            'merchant_webhook_deliveries',
            'audit_logs',
        ];

        foreach ($requiredTables as $table) {
            $checks[] = $this->check(Schema::hasTable($table), "Required table exists: {$table}", true);
        }

        $checks[] = $this->check(Schema::hasTable('migrations'), 'Migrations table exists', true);

        return $checks;
    }

    protected function mpesaChecks(): array
    {
        $productionMpesa = config('mpesa.environment') === 'production';
        $checks = [
            $this->check(in_array(config('mpesa.environment'), ['sandbox', 'production'], true), 'MPESA_ENV is valid', true),
            $this->check(filled(config('mpesa.callback_secret')), 'MPESA_CALLBACK_SECRET is set', true),
            $this->check(filled(config('mpesa.callback_url')), 'STK callback URL is configured', $productionMpesa),
            $this->check(! ($productionMpesa && config('mpesa.b2c.fake')), 'B2C fake mode is off for production M-Pesa', false),
        ];

        if ($productionMpesa) {
            foreach (['consumer_key', 'consumer_secret', 'shortcode', 'passkey'] as $key) {
                $checks[] = $this->check(filled(config("mpesa.{$key}")), 'M-Pesa production '.str_replace('_', ' ', $key).' configured', true);
            }
        }

        return $checks;
    }

    protected function securityChecks(): array
    {
        $securityExit = Artisan::call('payments:security-check');

        return [
            $this->check($securityExit === 0, 'payments:security-check passes', app()->environment('production')),
            $this->check(Route::has('legal.terms') && Route::has('legal.privacy'), 'Legal pages are routed', false),
            $this->check(str_contains((string) file_get_contents(resource_path('js/Pages/Public/Legal/Terms.vue')), 'Placeholder content'), 'Terms page is still marked for legal review', false),
            $this->check(in_array(\App\Providers\PaymentRateLimitServiceProvider::class, require base_path('bootstrap/providers.php'), true), 'Rate limit service provider is registered', true),
        ];
    }

    protected function operationsChecks(): array
    {
        return [
            $this->check(config('queue.default') !== 'sync' || ! app()->environment('production'), 'Queue workers required for production', app()->environment('production')),
            $this->check(file_exists(base_path('routes/console.php')), 'Scheduler routes are present', false),
            $this->check(filled(config('operations.alert_email')), 'Operations alert email configured', false),
            $this->check(Schema::hasTable('failed_jobs'), 'Failed jobs table exists', false),
            $this->check(file_exists(base_path('docs/PRODUCTION_READINESS.md')), 'Production readiness docs exist', false),
        ];
    }

    protected function adminChecks(): array
    {
        return [
            $this->check(User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->exists(), 'At least one admin user exists', app()->environment('production')),
            $this->check(Route::has('admin.dashboard'), 'Admin route is registered and middleware protected', true),
        ];
    }

    protected function moneySafetyChecks(): array
    {
        return [
            $this->check(Schema::hasTable('wallet_ledger_entries'), 'Wallet ledger table exists', true),
            $this->check(class_exists(\App\Console\Commands\ExpirePendingTransactions::class), 'Pending transaction expiration command exists', true),
            $this->check(class_exists(\App\Console\Commands\ReconciliationReport::class), 'Reconciliation report command exists', true),
            $this->check(method_exists(\App\Services\Payments\PayoutService::class, 'reverseFailedPayout'), 'Payout reversal logic exists', true),
        ];
    }

    protected function check(bool $passes, string $label, bool $critical): array
    {
        return [
            'status' => $passes ? 'PASS' : ($critical ? 'FAIL' : 'WARN'),
            'label' => $label,
            'critical' => $critical,
        ];
    }
}

