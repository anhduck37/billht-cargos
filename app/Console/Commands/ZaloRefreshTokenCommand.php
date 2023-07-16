<?php

namespace App\Console\Commands;

use App\Services\ZaloService;
use Illuminate\Console\Command;

class ZaloRefreshTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zalo_refresh_token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $zaloService = new ZaloService();
        $zaloConfig = $zaloService->refresh_token();
        echo 'Access token: ' . $zaloConfig->access_token . "\n";
    }
}
