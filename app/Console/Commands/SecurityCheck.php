<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SecurityCheck extends Command
{
    protected $signature = 'payments:security-check';

    protected $description = 'Check payment platform production security configuration without printing secrets.';

    public function handle(): int
    {
        $production = app()->environment('production');
        $criticalFailures = 0;

        $criticalFailures += $this->check(! ($production && config('app.debug')), 'APP_DEBUG is false in production', 'APP_DEBUG must be false in production', true);
        $criticalFailures += $this->check(filled(config('app.key')), 'APP_KEY is set', 'APP_KEY is missing', true);
        $criticalFailures += $this->check(filled(config('mpesa.callback_secret')), 'MPESA_CALLBACK_SECRET is set', 'MPESA_CALLBACK_SECRET is missing', true);
        $criticalFailures += $this->check(! ($production && config('queue.default') === 'sync'), 'Queue connection is production-safe', 'QUEUE_CONNECTION must not be sync in production', true);
        $criticalFailures += $this->check(! ($production && ! str_starts_with((string) config('app.url'), 'https://')), 'APP_URL uses HTTPS in production', 'APP_URL should be HTTPS in production', true);

        if (config('mpesa.environment') === 'production') {
            foreach (['consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'callback_url'] as $key) {
                $criticalFailures += $this->check(filled(config("mpesa.{$key}")), "MPESA_".strtoupper($key).' is configured', "MPESA_".strtoupper($key).' is missing', true);
            }
        }

        $this->check(config('mail.default') !== 'log', 'Mail provider is not log', 'Mail is using log driver; invoice emails may not leave the server');
        $this->check(config('queue.connections.'.config('queue.default')) !== null, 'Webhook/payout queue connection exists', 'Queue connection is not configured');
        $this->check(! (app()->environment('production') && (bool) config('mpesa.b2c.fake')), 'B2C fake mode is off in production', 'B2C fake mode is enabled in production');

        return $production && $criticalFailures > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function check(bool $passes, string $pass, string $fail, bool $critical = false): int
    {
        if ($passes) {
            $this->line("[PASS] {$pass}");
            return 0;
        }

        $this->line(($critical ? '[FAIL] ' : '[WARN] ').$fail);

        return $critical ? 1 : 0;
    }
}

