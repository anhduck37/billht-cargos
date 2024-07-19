<?php

namespace App\Jobs;

use App\Services\ViettelPostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderViettelPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $order;
    public $service_viettel;
    public function __construct($order, $service_viettel= null)
    {
        $this->order = $order;
        $this->service_viettel = $service_viettel;
    }

    public function handle()
    {
        $viettelPostService = new ViettelPostService($this->service_viettel);
        return $viettelPostService->createOrder($this->order);
    }
}
