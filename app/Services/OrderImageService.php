<?php
namespace App\Services;

use App\OrderImage;
use Illuminate\Support\Facades\File;

class OrderImageService {

    private $contentFile;
    private $mimeType;
    private $fileName;

    public function createOrUpdate($orderId, $data) {
        $order_image = new OrderImage();
        if($orderId) {
            $find_order_image = OrderImage::where('order_id', $orderId)->first();
            if(!empty($find_order_image)) {
                $order_image = $find_order_image;
                if($order_image->type_save == OrderImage::SAVE_GOOGLE_DRIVE) {
                    $gooleDriveService = new GoogleDriveService();
                    $gooleDriveService->deleteFile($order_image->file_id);
                } else {
                    $path = public_path(). "/uploads/". $order_image->image;
                    if (File::exists($path)) {
                        unlink($path);
                    }
                }
            }
        }
        $order_image->fill($data);
        $order_image->save();
        return $order_image;
    }

    public function setUp($file, $type_image, $order_code) {
        switch ($type_image) {
            case OrderImage::TYPE_IMAGE_FILE:
                $this->setContentFile(file_get_contents($file));
                $this->setMimeType($file->getClientMimeType());
                $this->setFileName($order_code. '.' .$file->getClientOriginalExtension());
                break;
            case OrderImage::TYPE_IMAGE_WEBCAM:
                $image_parts = explode(";base64,", $file);
                $image_type_aux = explode("image/", $image_parts[0]);
                $this->setContentFile(base64_decode($image_parts[1]));
                $this->setMimeType('image/'.$image_type_aux[1]);
                $this->setFileName($order_code . '.jpeg');
                break;
            case OrderImage::TYPE_IMAGE_PATH:
                $this->setContentFile(file_get_contents($file));
                $image_type_aux = explode("image/", mime_content_type($file));
                $this->setMimeType(mime_content_type($file));
                $this->setFileName($order_code. '.' .$image_type_aux[1]);
                break;
        }
        return $this;
    }

    public function setContentFile($content) {
        return $this->contentFile = $content;
    }

    public function getContentFile() {
        return $this->contentFile;
    }

    public function setMimeType($mimeType) {
        return $this->mimeType = $mimeType;
    }

    public function getMimeType() {
        return $this->mimeType;
    }

    public function setFileName($fileName) {
        return $this->fileName = $fileName;
    }

    public function getFileName() {
        return $this->fileName;
    }
}
