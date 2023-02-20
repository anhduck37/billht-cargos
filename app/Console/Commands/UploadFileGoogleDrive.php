<?php

namespace App\Console\Commands;

use App\OrderImage;
use App\Services\GoogleDriveService;
use App\Services\OrderImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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
        $googleDriveService = new GoogleDriveService();
        $orderImageService = new OrderImageService();
        OrderImage::with(['order'])->where('type_save', OrderImage::SAVE_SERVER)->groupBy('order_id')
        ->chunkById(1000, function($orderImages) use ($googleDriveService, $orderImageService) {
            foreach($orderImages as $item) {
                if(isset($item->order)) {
                    $path = public_path()."/uploads/";
                    if(File::exists($path . $item->image)) {
                        $fileImage = $orderImageService->setUp($path . $item->image, OrderImage::TYPE_IMAGE_PATH, $item->order->order_code);
                        $data = $googleDriveService->createFile($item->image, $fileImage->getContentFile(), $fileImage->getMimeType());
                        $item->google_drive_id = $data->getFolder()->id;
                        $item->file_id = $data->getFile()->id;
                        $item->url = config('google_drive.url') . $data->getFile()->id;
                        $item->type_save = OrderImage::SAVE_GOOGLE_DRIVE;
                        $item->save();
                        echo 'order_code: '.$item->order->order_code.' --> fileId: '. $item->file_id."\n";
                    }

                }
            }
        });
    }
}
