<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Api\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function detail($order_code)
    {
        return $this->orderService->detail($order_code);
    }


    public function renderTemplate(Request $request)
    {
        $orders = $request->order;
        if (!empty($orders)) {
            $orders = Order::whereIn('id', $orders)->get();
        } else {
            $orders = [];
        }
        $level = auth()->user()->level;
        return view('template.print', ['orders' => $orders, 'level' => $level])->render();
    }
}
