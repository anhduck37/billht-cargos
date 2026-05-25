<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderHistory;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ? $request->limit : 50;
        $orderHistorys = OrderHistory::with(['user', 'order'])->orderBy('id', 'desc');

        if (!$request->has('search_date') || $request->search_date == null) {
            $startDate = \Carbon\Carbon::now()->subMonths(1)->startOfDay();
            $endDate = \Carbon\Carbon::now()->endOfDay();
            $request->merge(['search_date' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')]);
        }

        if ($request->has('search_date') && $request->search_date != null) {
            $dates = explode(' - ', $request->search_date);
            if (count($dates) == 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
                $orderHistorys = $orderHistorys->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        if ($request->has('action') && $request->action != null) {
            if ($request->action == 'CREATE') {
                $orderHistorys = $orderHistorys->where('type_order', OrderHistory::TYPE_ORDER_CREATE);
            } else if ($request->action == 'UPDATE') {
                $orderHistorys = $orderHistorys->where('type_order', OrderHistory::TYPE_ORDER_UPDATE);
            } else if ($request->action == 'DELETE') {
                $orderHistorys = $orderHistorys->where('action', 'DELETE');
            } else if ($request->action == 'SYNC') {
                $orderHistorys = $orderHistorys->where('action', 'SYNC');
            }
        }

        if ($request->has('partner') && $request->partner != null) {
            $partner = strtoupper($request->partner);
            $partnerNames = [];

            if ($partner === Order::CODE_VIETTEL_POST || $partner === 'VIETTEL_POST') {
                $partnerNames = ['VTP', 'VIETTEL_POST', 'Viettel Post'];
            } elseif ($partner === Order::CODE_EMS) {
                $partnerNames = ['EMS'];
            } elseif ($partner === Order::TRACKING_PROVIDER_MICKEY) {
                $partnerNames = ['MICKEY', 'Mickey', 'QuangCPN (Mickey)'];
            }

            if (!empty($partnerNames)) {
                $orderHistorys = $orderHistorys->where(function ($query) use ($partner, $partnerNames) {
                    $query->whereIn('partner_name', $partnerNames)
                        ->orWhere('data', 'LIKE', '%"partner_code":"' . $partner . '"%')
                        ->orWhere('data', 'LIKE', '%"tracking_provider":"' . $partner . '"%');
                });
            }
        }

        if ($request->has('search') && $request->search != null) {
            $orderHistorys = $orderHistorys->where(function ($query) use ($request) {
                $query->whereHas('order', function ($orderQuery) use ($request) {
                    $orderQuery->where('order_code', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('tracking_code', 'LIKE', '%' . $request->search . '%');
                })->orWhere('tracking_code', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->has('email') && $request->email != null) {
            $orderHistorys = $orderHistorys->whereHas('user', function ($query) use ($request) {
                $query->where('email', 'LIKE', '%' . $request->email . '%');
            });
        }

        $orderHistorys = $orderHistorys->paginate($limit);
        return view('order_historys.index', compact('orderHistorys'));
    }
}
