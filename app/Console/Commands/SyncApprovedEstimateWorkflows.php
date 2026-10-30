<?php
namespace App\Console\Commands;

use App\Models\Estimate;
use App\Services\EstimateFulfilmentService;
use Illuminate\Console\Command;

class SyncApprovedEstimateWorkflows extends Command
{
    protected $signature = 'estimates:sync-workflows';
    protected $description = 'Create missing deals and installation workflows for approved estimates without duplicating records';

    public function handle(EstimateFulfilmentService $service): int
    {
        $count = 0;
        Estimate::where('status', 'approved')->whereNotNull('customer_id')->orderBy('estimate_id')->chunkById(100, function ($estimates) use ($service, &$count) {
            foreach ($estimates as $estimate) {
                if (!$estimate->customer) continue;
                $service->ensure($estimate);
                $count++;
            }
        }, 'estimate_id');
        $this->info('Synchronized '.$count.' approved estimates.');
        return self::SUCCESS;
    }
}
