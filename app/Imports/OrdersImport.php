<?php

namespace App\Imports;

use App\Models\Order;
use App\Receiver;
use App\Sender;
use App\Service;
use App\Services\OrderService;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;

class OrdersImport implements ToModel
{
    public function model(array $row)
    {
//        DB::beginTransaction();
//        try {
                dd($row);
                $sender = [
                    'sender_name' => array_key_exists(1, $row) ? $row[1] : $row['sender_name'],
                    'sender_phone' => array_key_exists(4, $row) ? $row[4] : $row['sender_phone'],
                ];
                $receiver = [
                    'receiver_name' =>array_key_exists(3, $row) ? $row[3] : $row['receiver_name'],
                    'address' => array_key_exists(5, $row) ? $row[5]: $row['address'],
                    'receiver_phone' => array_key_exists(6, $row) ? $row[6] : $row['receiver_phone'],
                    'receiver_email' => array_key_exists(7, $row) ? $row[7] : $row['receiver_email']
                ];
                $sender = Sender::create($sender);
                $receiver = Receiver::create($receiver);
                $order = [
                    'sender_id' => isset($sender) ? $sender->id : 0,
                    'receiver_id' => isset($receiver) ? $receiver->id: 0,
                    'order_date' => array_key_exists(1, $row) ? $row[0] : $row['order_date'],
                    'department' => array_key_exists(1, $row) ? $row[2] : $row['department'],
                    'weight' => array_key_exists(1, $row) ? $row[8] : $row['weight'],
                    'note' => array_key_exists(1, $row) ? $row[12] : $row['note'],
                    'invoice_code' => array_key_exists(1, $row) ? $row[13] : $row['invoice_code'],
                    'user_id' => auth()->user()->id,
                    'order_status' => Order::ORDER_BLANK,
                ];
                $order['order_code'] =
                $orderInfo = Order::create($order);
                $dataService = [];
                if(array_key_exists(9, $row)){
                    $infoService = app(OrderService::class)->getKeyService($row[9]);
                    if($infoService && array_key_exists('type', $infoService) && array_key_exists('service_key', $infoService)) {
                        $dataService[$infoService['type']][] = $infoService['service_key'];
                    }
                }

                if(array_key_exists(10, $row)) {
                    $service_extra = isset($row[11]) ? explode(',', $row[10]): [];
                    foreach ($service_extra as $service_name) {
                        $item = app(OrderService::class)->getKeyService(trim($service_name));
                        if($item && array_key_exists('type', $item) && array_key_exists('service_key', $item)){
                            $dataService[$item['type']][] = $item['service_key'];
                        }
                    }
                    if($orderInfo){
                        app(OrderService::class)->insertService($dataService, $orderInfo->id);
                    }
                }
                return $orderInfo;
//            DB::commit();
//        }catch (Exception $e) {
//            DB::rollback();
//        }

    }
}
