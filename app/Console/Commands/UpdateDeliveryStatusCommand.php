<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateDeliveryStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    private $one_day = 1;
    private $seven_day = 7;
    protected $signature = 'update_delivery_status {--type=}';

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
        $type = $this->option('type');
        $time = Carbon::now()->subDay($type);
        $startTime = $time->format('Y-m-d H:i');
        $endTime = $time->addMinutes(1)->format('Y-m-d H:i');
        $delivery_status = [];
        $delivery_status_update = 0;
        switch ($type) {
            case $this->one_day:
                $delivery_status = [Order::DELIVERY_STATUS_RETURN, Order::DELIVERY_STATUS_BLANK];
                $delivery_status_update = Order::DELIVERY_STATUS_PERSON_CHARGE;
                break;
            case $this->seven_day:
                $delivery_status = [Order::DELIVERY_STATUS_PERSON_CHARGE];
                $delivery_status_update = Order::DELIVERY_STATUS_OK;
                break;
        }
        if(!empty($delivery_status)) {
            Order::whereIn('delivery_status', $delivery_status)
                    ->where('created_at', '>=' , $startTime)
                    ->where('created_at', '<', $endTime)
                    ->update(['delivery_status' => $delivery_status_update]);
        }
    }
}
