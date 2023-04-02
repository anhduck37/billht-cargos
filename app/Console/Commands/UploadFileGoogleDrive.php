<?php

namespace App\Console\Commands;

use App\Jobs\UploadGoogleDriveJob;
use App\OrderImage;
use Illuminate\Console\Command;

class UploadFileGoogleDrive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload_google_drive';

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
        OrderImage::with(['order'])->where('type_save', OrderImage::SAVE_SERVER)->groupBy('order_id')
        ->chunkById(1000, function($orderImages) {
            foreach($orderImages as $item) {
                if(isset($item->order)) {
                    dispatch(new UploadGoogleDriveJob($item->order));
                    echo 'order_code: '.$item->order->order_code."\n";
                }
            }
        });
    }
}
