<?php

namespace App\Jobs;

use App\OrderImage;
use App\Services\GoogleDriveService;
use App\Services\OrderImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class UploadGoogleDriveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $orderImageService = new OrderImageService();
        $googleDriveService = new GoogleDriveService();
        if(isset($this->order->image)) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $this->order->created_at);
            $month = $date->format('m');
            $year = $date->format('Y');
            $folderPath = public_path()."/uploads/";
            $file = $folderPath . $this->order->image->image;
            $fileImage = $orderImageService->setUp($file, OrderImage::TYPE_IMAGE_PATH, $this->order->order_code);
            $fileName = $fileImage->getFileName();
            $data = $googleDriveService->createFile(
                $fileName,
                $fileImage->getContentFile(),
                $fileImage->getMimeType(),
                $month,
                $year
            );
            if(!empty($this->order->image->file_id)) {
                $googleDriveService->deleteFile($this->order->image->file_id);
            }
            if(isset($data->getFile()->id)) {
                $dataOrderImage['google_drive_id'] = $data->getFolder()->id;
                $dataOrderImage['file_id'] = $data->getFile()->id;
                $dataOrderImage['url'] = config('google_drive.url') . $data->getFile()->id;
                $dataOrderImage['type_save'] = OrderImage::SAVE_GOOGLE_DRIVE;
                $orderImageService->createOrUpdate($this->order->id, $dataOrderImage, true);
            }
        }
    }
}
