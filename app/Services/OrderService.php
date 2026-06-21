<?php

namespace App\Services;

use App\Models\Order;
use App\Partner;
use App\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OrderService
{
    public $order_id_current = 1;

    public function getOrderCode($prefix) {
        $prefix = (string) $prefix;

        if (DB::transactionLevel() === 0) {
            return DB::transaction(function () use ($prefix) {
                return $this->reserveOrderCode($prefix);
            });
        }

        return $this->reserveOrderCode($prefix);
    }

    private function reserveOrderCode($prefix)
    {
        $this->ensureOrderCodeCounter($prefix);

        $counter = DB::table('order_code_counters')
            ->where('prefix', $prefix)
            ->lockForUpdate()
            ->first();

        $nextNumber = max((int) $counter->next_number, $this->getInitialNextNumber($prefix));

        do {
            $this->order_id_current = $nextNumber;
            $order_code = $this->genCode($prefix);
            $nextNumber++;
        } while (Order::where('order_code', $order_code)->exists());

        DB::table('order_code_counters')
            ->where('prefix', $prefix)
            ->update([
                'next_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $order_code;
    }

    private function ensureOrderCodeCounter($prefix)
    {
        if (DB::table('order_code_counters')->where('prefix', $prefix)->exists()) {
            return;
        }

        $now = now()->format('Y-m-d H:i:s');
        DB::statement(
            'INSERT IGNORE INTO order_code_counters (`prefix`, `next_number`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?)',
            [$prefix, $this->getInitialNextNumber($prefix), $now, $now]
        );
    }

    public function getInitialNextNumber($prefix)
    {
        $maxIdNext = ((int) Order::max('id')) + 1;
        $maxPrefixNumber = 0;

        if ($prefix !== '') {
            $start = strlen($prefix) + 1;
            $maxSuffixLength = max(6, strlen((string) $maxIdNext));
            $result = Order::where('order_code', 'LIKE', $prefix . '%')
                ->whereRaw('SUBSTRING(order_code, ' . (int) $start . ') REGEXP "^[0-9]+$"')
                ->whereRaw('CHAR_LENGTH(SUBSTRING(order_code, ' . (int) $start . ')) <= ?', [$maxSuffixLength])
                ->selectRaw('MAX(CAST(SUBSTRING(order_code, ' . (int) $start . ') AS UNSIGNED)) as max_number')
                ->first();
            $maxPrefixNumber = $result ? (int) $result->max_number : 0;
        }

        return max(1, $maxIdNext, $maxPrefixNumber + 1);
    }

    public function genCode($prefix) {
        for ($i = 0; $i < 6 - strlen($this->order_id_current); $i++){
            $prefix .= '0';
        }
        return $prefix.($this->order_id_current);
    }

    public function explodeDate($date) {
        $times = explode('/',$date);
        $convertDate = $times[2].'-'.$times[1].'-'.$times[0];
        return $convertDate;
    }

    public function implodeDate($date) {
        if(strtotime($date)){
            $times = explode('-',$date);
            $convertDate = $times[2].'/'.$times[1].'/'.$times[0];
            return $convertDate;
        }
        return null;
    }

    public function getKeyService($service) {
        $data = null;
        $value = ucfirst(mb_strtolower(trim($service), 'UTF-8'));
        foreach (Service::SERVICE_MAP as $key => $item) {
            if(in_array($value, $item['value'])){
                $service_key = array_search($value, $item['value']);
                $service_type = $key;
                $data = ['type' => $key, 'service_key' => $service_key];
            }
        }
        return Service::VIETTEL_POST_SERVICE_ADD[$service] ?? $data;
    }

    public function getType($type) {
        $data = Order::ORDER_TYPE_DOCUMENT;
        $value = ucfirst(mb_strtolower(trim($type), 'UTF-8'));
        foreach (Order::MAP_ORDER_TYPE as $key => $item) {
            if($value == ucfirst(mb_strtolower(trim($item), 'UTF-8'))) {
                $data = $key;
                break;
            }
        }
        return $data;
    }

    public function getKeyPaymentMethod($name) {
        $convertName = ucfirst(mb_strtolower(trim($name), 'UTF-8'));
        if($convertName == 'Cod') {
            $convertName = 'COD';
        }
        $key = array_search($convertName, Order::PAYMENT_METHOD_MAP);
        if($key) {
            return $key;
        }
        return 0;
    }

    public function insertService($services, $order_id) {
        $data = [];
        foreach ($services as $key => $value) {
            foreach ($value as $item) {
                $dataOrdeService = [
                    'order_id' => $order_id,
                    'service' => $item,
                    'type' => $key
                ];
                array_push($data, $dataOrdeService);
            }
        }
        if(!empty($data)){
            Service::insert($data);
        }
    }

    public function renameImage($orderImage) {
        $regex = '/\.[a-z]*$/';
        $path = public_path()."/uploads/";
        if(File::exists($path . $orderImage->image)) {
            $name = preg_replace($regex, '', $orderImage->image);
            $new_name = str_replace($name, $orderImage->order->order_code, $orderImage->image);
            rename($path . $orderImage->image, $path . $new_name);
            $orderImage->image = $new_name;
            $orderImage->save();
        }
    }
}
