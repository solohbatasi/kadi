<?php

namespace App\Console\Commands;

use App\Services\Operations\PreLiveCheckService;
use Illuminate\Console\Command;

class PreLiveCheck extends Command
{
    protected $signature = 'payments:prelive-check';

    protected $description = 'Run the final production launch readiness checklist.';

    public function handle(PreLiveCheckService $checks): int
    {
        $results = $checks->results();

        foreach ($results as $section => $items) {
            $this->newLine();
            $this->line(strtoupper(str_replace('_', ' ', $section)));

            foreach ($items as $item) {
                $this->line("[{$item['status']}] {$item['label']}");
            }
        }

        return app()->environment('production') && $checks->hasCriticalFailures($results)
            ? self::FAILURE
            : self::SUCCESS;
    }
}

