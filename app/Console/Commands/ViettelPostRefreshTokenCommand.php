<?php

namespace App\Console\Commands;

use App\Services\ViettelPostService;
use Illuminate\Console\Command;
use App\Models\Order;

class ViettelPostRefreshTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'viettel_post_refresh_token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $viettelPostService = new ViettelPostService();
        $result = $viettelPostService->refreshToken();

        if (!empty($result['data']['token'])) {
            $this->info('Viettel Post token refreshed successfully.');
            return 0;
        }

        $this->error('Viettel Post token refresh failed: ' . ($result['message'] ?? 'Unknown error'));
        return 1;
    }
}
