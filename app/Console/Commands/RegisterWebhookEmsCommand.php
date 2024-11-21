<?php

namespace App\Console\Commands;

use App\Services\EmsService;
use Illuminate\Console\Command;

class RegisterWebhookEmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register_webhook {--status=1} {--link=} {--update=1}';

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $link = $this->option('link');
        $status = $this->option('status');
        $update = (int)$this->option('update');
        $emsService = new EmsService();
        if ($update) {
            $result = $emsService->updateWebhook($link, $status);
        } else {
            $result = $emsService->createWebhook($link);
        }

        dd($result);
    }
}
