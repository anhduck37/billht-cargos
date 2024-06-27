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
    public function __construct($order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        $viettelPostService = new ViettelPostService();
        return $viettelPostService->createOrder($this->order);
    }
}
