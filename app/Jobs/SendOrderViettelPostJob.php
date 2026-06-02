<?php

namespace App\Jobs;

use App\Service;
use App\Models\Order;
use App\Services\PartnerErrorMessageService;
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
        if(empty($service_viettel)) {
            $orderService = Service::where('order_id', $this->order->id)
            ->where('type', Service::SERVICE_DOMESTIC)
            ->where(function($q) {
                $q->where('service', Service::CPN)
                ->orWhere('service', Service::TK);
            })->first();
            if($orderService) {
                $this->service_viettel = Service::VIETTEL_POST_SERVICE[$orderService->service];
            }
        }
        
        $viettelPostService = new ViettelPostService($this->service_viettel);
        $result = $viettelPostService->createOrder($this->order);

        // Reload order để tránh stale data
        $this->order->refresh();

        if (!empty($result['data'])) {
            // Push thành công → xóa lỗi cũ nếu có
            if ($this->order->push_error) {
                $this->order->push_error = null;
                $this->order->save();
            }
        } else {
            // Push thất bại → ghi lỗi chi tiết vào DB
            $errors = $result['message'] ?? 'Push VTP thất bại (không có chi tiết lỗi)';
            $errors = app(PartnerErrorMessageService::class)->normalizeText(Order::CODE_VIETTEL_POST, $errors);
            $this->order->push_error = '[VTP] ' . $errors;
            $this->order->save();
        }

        return $result;
    }
}
