<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\OrderHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateDeliveryStatusPrintCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_delivery_status_print';

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
        $delivery_status = [Order::DELIVERY_STATUS_RETURN, Order::DELIVERY_STATUS_BLANK, Order::DELIVERY_STATUS_PROCESSING];
        $delivery_status_update = Order::DELIVERY_STATUS_PERSON_CHARGE;
        $subDay = config('update_delivery_status.day_ago_update_bill');
        $time = Carbon::now()->subDay($subDay);
        $startTime = $time->format('Y-m-d H:i');
        $endTime = $time->addMinutes(10)->format('Y-m-d H:i');
        echo $startTime . ' - ' . $endTime . "\n";
        Order::join('order_historys', function ($q) {
            $q->on('order_historys.order_id', '=', 'orders.id')
                ->where('order_historys.type_order', '=', OrderHistory::TYPE_ORDER_PRINT);
        })
            ->whereIn('orders.delivery_status', $delivery_status)
            ->where('order_historys.created_at', '>=', $startTime)
            ->where('order_historys.created_at', '<', $endTime)
            ->update(['orders.delivery_status' => $delivery_status_update]);
    }
}
