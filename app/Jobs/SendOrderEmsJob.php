<?php

namespace App\Jobs;

use App\Services\EmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderEmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        $emsService = new EmsService();
        $result = $emsService->createOrder($this->order);

        // Reload order để tránh stale data
        $this->order->refresh();

        if (isset($result['code']) && $result['code'] === EmsService::STATUS_SUCCESS) {
            // Push thành công → xóa lỗi cũ nếu có
            if ($this->order->push_error) {
                $this->order->push_error = null;
                $this->order->save();
            }
        } else {
            // Push thất bại → ghi lỗi chi tiết vào DB
            $errors = '';
            if (!empty($result['data']) && is_array($result['data'])) {
                // Trường hợp EMS trả về mảng lỗi validation [{Parameter, Message}]
                $errorItems = [];
                foreach ($result['data'] as $item) {
                    if (isset($item['Parameter']) && isset($item['Message'])) {
                        $errorItems[] = $item['Parameter'] . ': ' . $item['Message'];
                    }
                }
                $errors = implode('; ', $errorItems);
            }
            if (empty($errors)) {
                $errors = $result['message'] ?? 'Push EMS thất bại (không có chi tiết lỗi)';
            }
            $this->order->push_error = $errors;
            $this->order->save();
        }

        return $result;
    }
}
