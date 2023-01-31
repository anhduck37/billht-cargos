<?php

namespace App\Console\Commands;

use App\OrderImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateFileNameImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_file_name_image';

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
        $regex = '/\.[a-z]*$/';
        OrderImage::with(['order'])->groupBy('order_id')->chunkById(1000, function($orderImages) use ($regex) {
            foreach($orderImages as $item) {
                if(isset($item->order)) {
                    $path = public_path()."/uploads/";
                    if(File::exists($path . $item->image)) {
                        $name = preg_replace($regex, '', $item->image);
                        $new_name = str_replace($name, $item->order->order_code, $item->image);
                        rename($path . $item->image, $path . $new_name);
                        $item->image = $new_name;
                        echo 'order_code: '.$item->order->order_code.' --> '. $new_name."\n";
                        $item->save();
                    }

                }
            }
        });
    }
}
