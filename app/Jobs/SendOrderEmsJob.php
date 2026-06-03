<?php

namespace App\Jobs;

use App\Services\ApiSenderAddressService;
use App\Services\EmsService;
use App\Services\PartnerErrorMessageService;
use App\Models\Order;
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
        app(ApiSenderAddressService::class)->ensureDefaultSenderAddressForApi($this->order, Order::CODE_EMS);

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
                    if (is_array($item)) {
                        if (isset($item['Parameter']) && isset($item['Message'])) {
                            $errorItems[] = $item['Parameter'] . ': ' . $item['Message'];
                        } elseif (is_string($item)) {
                            $errorItems[] = $item;
                        }
                    } elseif (is_string($item)) {
                        $errorItems[] = $item;
                    }
                }
                $errors = implode('; ', $errorItems);
            }
            if (empty($errors)) {
                $errors = $result['message'] ?? 'Push EMS thất bại (không có chi tiết lỗi)';
            }
            $errors = app(PartnerErrorMessageService::class)->normalizeText(Order::CODE_EMS, $errors);
            $this->order->push_error = $errors;
            $this->order->save();
            
            \Log::warning('EMS Job Failed for Order', [
                'order_id' => $this->order->id,
                'error' => $errors
            ]);
        }

        return $result;
    }
}
