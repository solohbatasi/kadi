<?php

namespace App\Console\Commands;

use App\Services\Operations\OperationalAlertService;
use Illuminate\Console\Command;

class CheckOperationalAlerts extends Command
{
    protected $signature = 'operations:check-alerts';

    protected $description = 'Check operational payment alert thresholds.';

    public function handle(OperationalAlertService $alerts): int
    {
        $items = $alerts->check();

        if ($items === []) {
            $this->info('No operational alerts.');
            return self::SUCCESS;
        }

        $this->warn(count($items).' operational alert(s) detected.');
        $this->line(json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}

