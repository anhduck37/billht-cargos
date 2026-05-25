<?php

namespace App\Console\Commands;

use App\Services\ApiStatusService;
use Illuminate\Console\Command;

class ApiCheckStatusCommand extends Command
{
    protected $signature = 'api:check-status {provider?}';

    protected $description = 'Check current API availability for Viettel, EMS and Mickey';

    public function handle(ApiStatusService $apiStatusService)
    {
        $provider = $this->argument('provider');
        $statuses = $provider
            ? [$provider => $apiStatusService->check($provider)]
            : $apiStatusService->checkAll();

        foreach ($statuses as $status) {
            $this->line(sprintf(
                '%s: %s (%s)',
                $status['name'],
                $status['online'] ? 'ONLINE' : 'OFFLINE',
                $status['message']
            ));
        }

        return 0;
    }
}
