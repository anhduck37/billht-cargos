<?php

namespace App\Http\Controllers;

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

        if ($request->has('search') && $request->search != null) {
            $orderHistorys = $orderHistorys->whereHas('order', function ($query) use ($request) {
               $query->where('order_code', 'LIKE', '%' . $request->search . '%')
                   ->orWhere('tracking_code', 'LIKE', '%' . $request->search . '%');
            })->orWhere('tracking_code', 'LIKE', '%' . $request->search . '%');
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
