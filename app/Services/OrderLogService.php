<?php
namespace App\Services;

use App\OrderLog;
use Illuminate\Support\Facades\Route;

class OrderLogService {
    protected $model;

    public function __construct(OrderLog $orderLog) {
        $this->model = $orderLog;
    }

    public function create($order, $request, $response, $action) {
        if(!is_string($request)) {
            $request = json_encode($request);
        }
        if(!is_string($response)) {
            $response = json_encode($response);
        }
        $data = [
            'order_code' => $order->order_code,
            'request' => $request,
            'response' => $response,
            'action' => $action,
            'path' => Route::getCurrentRoute()->getPath()
        ];
        $orderLog = $this->model->fill($data);
        $orderLog->save();
        return $orderLog;
    }
}