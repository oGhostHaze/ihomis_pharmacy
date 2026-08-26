<?php

namespace App\Console\Commands;

use App\Services\Pharmacy\UdddsService;
use Illuminate\Console\Command;

class GenerateUdddsDailyOrders extends Command
{
    protected $signature = 'uddds:generate-daily
        {--dry-run : Preview clones without inserting them}
        {--date= : Generate for a specific date (Y-m-d)}';

    protected $description = 'Create pending UDDDS unit-dose orders for enrolled Basic (standing) meds';

    public function handle(UdddsService $udddsService)
    {
        $run = $udddsService->generateDaily($this->option('date') ?: null, (bool) $this->option('dry-run'));

        $this->info('UDDDS generate run at ' . $run['run_at']);
        $this->line('Date: ' . $run['date']);
        $this->line(($run['dry_run'] ? 'Would create' : 'Created') . ': ' . $run['count']);
        $this->line('Skipped: ' . $run['skipped']);

        if (!($run['ok'] ?? true) && !empty($run['message'])) {
            $this->error($run['message']);
            return 1;
        }

        return 0;
    }
}
