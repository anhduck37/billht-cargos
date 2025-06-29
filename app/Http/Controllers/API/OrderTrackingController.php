<?php

namespace App\Http\Controllers\API;

use App\Services\Api\OrderTrackinService;
use App\Http\Controllers\Controller;

class OrderTrackingController extends Controller
{
    protected $orderTrackinService;

    public function __construct(OrderTrackinService $orderTrackinService)
    {
        $this->orderTrackinService = $orderTrackinService;
    }

    public function list($order_code)
    {
        return $this->orderTrackinService->list($order_code);
    }
}
