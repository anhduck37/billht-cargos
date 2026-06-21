<?php

namespace App\Services;

use App\Models\Order;
use App\OrderCodeAlias;

class OrderCodeAliasService
{
    public function findOrderByCode($code, array $with = [])
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $order = Order::with($with)->where('order_code', $code)->first();
        if ($order) {
            return $order;
        }

        $alias = OrderCodeAlias::where('old_code', $code)->orderBy('id')->first();
        return $alias ? Order::with($with)->where('id', $alias->order_id)->first() : null;
    }

    public function applySearchFilter($query, $keyword)
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '') {
            return $query;
        }

        return $query->orWhere('orders.order_code', 'LIKE', '%' . $keyword . '%')
            ->orWhere('orders.invoice_code', 'LIKE', '%' . $keyword . '%')
            ->orWhereExists(function ($subQuery) use ($keyword) {
                $subQuery->selectRaw('1')
                    ->from('order_code_aliases')
                    ->whereColumn('order_code_aliases.order_id', 'orders.id')
                    ->where(function ($aliasQuery) use ($keyword) {
                        $aliasQuery->where('order_code_aliases.old_code', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('order_code_aliases.new_code', 'LIKE', '%' . $keyword . '%');
                    });
            });
    }

    public function createAlias(Order $order, $oldCode, $newCode, $reason = null, $createdBy = null)
    {
        $oldCode = trim((string) $oldCode);
        $newCode = trim((string) $newCode);

        if ($oldCode === '' || $newCode === '' || $oldCode === $newCode) {
            return null;
        }

        return OrderCodeAlias::updateOrCreate(
            [
                'order_id' => $order->id,
                'old_code' => $oldCode,
            ],
            [
                'new_code' => $newCode,
                'reason' => $reason,
                'created_by' => $createdBy,
            ]
        );
    }
}
