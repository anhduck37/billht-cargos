<?php

namespace App\Services;

use App\OrderHistory;

class OrderHistoryService
{
    public function createOrderHistory($order_old, $order_new, $dataForm, $is_total_order, $type_order, $action = null, $dataInfo = null, $tracking_code = null, $partnerName = null, $sender_old = null, $sender_new = null, $receiver_old = null, $receiver_new = null)
    {
        $data = [
            'user_id' => auth()->user() ? auth()->user()->id : 0,
            'order_id' => $order_new ? $order_new->id : null,
            'user_level' => auth()->user() ? auth()->user()->level : 0,
            'is_total_order' => $is_total_order,
            'type_order' => $type_order,
            'action' => $action,
            'data' => $dataInfo ? (is_string($dataInfo) ? $dataInfo : json_encode($dataInfo, JSON_UNESCAPED_UNICODE)) : null,
            'tracking_code' => $tracking_code,
            'partner_name' => $partnerName
        ];

        // Nếu là thao tác CREATE/UPDATE đơn hàng, tự suy luận action nếu chưa truyền
        if (empty($action) && $order_new) {
            if ($type_order == OrderHistory::TYPE_ORDER_CREATE) {
                $data['action'] = 'CREATE';
            } elseif ($type_order == OrderHistory::TYPE_ORDER_UPDATE) {
                $data['action'] = 'UPDATE';
            } elseif ($type_order == OrderHistory::TYPE_ORDER_PRINT) {
                $data['action'] = 'PRINT';
            }
        }

        // Tạo order history thông thường
        if ($order_new) {
            // Tự động detect changes
            if (empty($dataInfo) && $type_order == OrderHistory::TYPE_ORDER_UPDATE) {
                $changes = [];
                
                // 1. So sánh Order
                if ($order_old) {
                    $orderMap = [
                        'order_date' => 'Ngày gửi',
                        'order_code' => 'Mã vận đơn',
                        'invoice_code' => 'Mã tham chiếu',
                        'long' => 'Dài',
                        'width' => 'Rộng',
                        'height' => 'Cao',
                        'weight' => 'Trọng lượng',
                        'total' => 'Tổng tiền',
                        'collection' => 'Tiền thu hộ (COD)',
                        'delivery_status' => 'Trạng thái',
                        'note' => 'Ghi chú',
                        'quantity' => 'Số lượng',
                        'person_charge' => 'Người phụ trách',
                        'signator' => 'Người nhận/Ký tên',
                        'type' => 'Loại hàng',
                        'payment_method' => 'Thanh toán',
                    ];

                    foreach($order_new->getAttributes() as $key => $value) {
                        if($key != 'updated_at' && $order_old->$key != $value) {
                            $oldVal = $order_old->$key;
                            $newVal = $value;

                            if ($key == 'delivery_status') {
                                $oldVal = \App\Models\Order::DELIVERY_MAP[(int)$oldVal] ?? $oldVal;
                                $newVal = \App\Models\Order::DELIVERY_MAP[(int)$newVal] ?? $newVal;
                            }
                            if ($key == 'type') {
                                $oldVal = \App\Models\Order::MAP_ORDER_TYPE[(int)$oldVal] ?? $oldVal;
                                $newVal = \App\Models\Order::MAP_ORDER_TYPE[(int)$newVal] ?? $newVal;
                            }
                            if ($key == 'payment_method') {
                                $oldVal = \App\Models\Order::PAYMENT_METHOD_MAP[(int)$oldVal] ?? $oldVal;
                                $newVal = \App\Models\Order::PAYMENT_METHOD_MAP[(int)$newVal] ?? $newVal;
                            }

                            $changes[$orderMap[$key] ?? $key] = ['old' => $oldVal, 'new' => $newVal];
                        }
                    }
                }

                // 2. So sánh Sender (Người gửi)
                if ($sender_old && $sender_new) {
                    $senderMap = [
                        'sender_name' => 'Tên người gửi',
                        'sender_phone' => 'SĐT người gửi',
                        'sender_email' => 'Email người gửi',
                        'address' => 'Địa chỉ người gửi',
                    ];
                    foreach($senderMap as $key => $label) {
                        if($sender_old->$key != $sender_new->$key) {
                            $changes[$label] = ['old' => $sender_old->$key, 'new' => $sender_new->$key];
                        }
                    }
                }

                // 3. So sánh Receiver (Người nhận)
                if ($receiver_old && $receiver_new) {
                    $receiverMap = [
                        'receiver_name' => 'Tên người nhận',
                        'receiver_phone' => 'SĐT người nhận',
                        'receiver_email' => 'Email người nhận',
                        'address' => 'Địa chỉ người nhận',
                    ];
                    foreach($receiverMap as $key => $label) {
                        if($receiver_old->$key != $receiver_new->$key) {
                            $changes[$label] = ['old' => $receiver_old->$key, 'new' => $receiver_new->$key];
                        }
                    }
                }

                if (!empty($changes)) {
                     $data['data'] = json_encode(['action_desc' => 'Cập nhật vận đơn', 'changes' => $changes], JSON_UNESCAPED_UNICODE);
                }
            } elseif (empty($dataInfo) && $type_order == OrderHistory::TYPE_ORDER_CREATE) {
                 $data['data'] = json_encode(['action_desc' => 'Tạo mới vận đơn'], JSON_UNESCAPED_UNICODE);
            }
        }

        $orderHistory = new OrderHistory();
        $orderHistory->fill($data);
        $orderHistory->save();
        return $orderHistory;
    }

    public function insertManyOrderHistory($orders, $type_order, $is_total_order = OrderHistory::NOT_TOTAL_ORDER)
    {
        $insertData = [];
        $action = null;
        $dataContent = null;

        if ($type_order == OrderHistory::TYPE_ORDER_PRINT) {
            $action = 'PRINT';
            $dataContent = json_encode(['action_desc' => 'In vận đơn'], JSON_UNESCAPED_UNICODE);
        }

        foreach ($orders as $order) {
            $item = [
                'user_id' => auth()->user() ? auth()->user()->id : 0,
                'order_id' => $order->id,
                'user_level' => auth()->user() ? auth()->user()->level : 0,
                'is_total_order' => $is_total_order,
                'type_order' => $type_order,
                'action' => $action,
                'data' => $dataContent,
                'created_at' => now(),
                'updated_at' => now()
            ];
            array_push($insertData, $item);
        }
        if (count($insertData) <= 0) {
            return;
        }
        OrderHistory::insert($insertData);
    }
}
