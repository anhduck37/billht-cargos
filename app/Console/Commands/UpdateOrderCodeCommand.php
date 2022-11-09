<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateOrderCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_order_code';

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
        $order_duplicate = [];
        $orders = Order::whereNotNull('invoice_code')->Where('invoice_code', '!=', '')->orderBy('id', 'DESC');
        $orders->chunkById(1000, function($items) use(&$order_duplicate) {
            foreach($items as $item) {
                echo 'Mã vận đơn: ' . $item->order_code . "\n";
                echo 'Mã khác: ' . $item->invoice_code . "\n";
                echo '.............................' . "\n";
                $checkOrder = Order::where('order_code', $item->invoice_code)->first();
                if($checkOrder) {
                    $order_duplicate[] = [
                        'Mã vận đơn' => $item->order_code,
                        'Mã khác' => $item->invoice_code
                    ];
                } else {
                    $item->order_code = $item->invoice_code;
                    $item->save();
                }
            }
            Log::info(json_encode($order_duplicate));
        });

        dd($order_duplicate);
    }
}
