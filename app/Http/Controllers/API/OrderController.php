<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function renderTemplate(Request $request) {
        $orders = $request->order;
        if(!empty($orders)) {
            $orders = Order::whereIn('id', $orders)->get();
        }else {
            $orders = [];
        }
        $level = auth()->user()->level;
        return view('template.print', ['orders' => $orders, 'level' => $level])->render();
    }
}
