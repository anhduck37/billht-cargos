<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MickeyService;
use App\Services\MickeyTrackingSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MickeyDetectOrdersCommand extends Command
{
    protected $signature = 'mickey:detect-orders {--dry-run} {--limit=} {--date-from=} {--date-to=} {--order-code=}';

    protected $description = 'Detect orders that have Mickey tracking data and mark tracking_provider as MICKEY';

    public function handle(MickeyService $mickeyService, MickeyTrackingSyncService $syncService)
    {
        if (!config('tracking.mickey_sync_enabled')) {
            $this->info('Mickey sync is disabled.');
            return 0;
        }

        $dryRun = (bool)$this->option('dry-run');
        $orderCode = trim((string)$this->option('order-code'));
        $limit = (int)($this->option('limit') ?: config('tracking.mickey_detect_limit', 300));
        $dateFrom = $this->option('date-from')
            ? Carbon::parse($this->option('date-from'))->startOfDay()
            : Carbon::yesterday()->startOfDay();
        $dateTo = $this->option('date-to')
            ? Carbon::parse($this->option('date-to'))->endOfDay()
            : Carbon::now();

        if ($orderCode !== '') {
            $orders = Order::where(function ($query) use ($orderCode) {
                $query->where('order_code', $orderCode)
                    ->orWhere('invoice_code', $orderCode);
            })->limit(1)->get();
        } else {
            $orders = Order::whereNull('partner_code')
                ->whereNull('order_partner_code')
                ->whereNull('tracking_provider')
                ->where('delivery_status', '!=', Order::DELIVERY_STATUS_OK)
                ->whereBetween('order_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        $detected = 0;
        $updated = 0;

        foreach ($orders as $order) {
            $tracking = $mickeyService->tracking($order, $order->order_code);
            if (!$syncService->hasTrackingData($tracking)) {
                $this->line("MISS {$order->order_code}");
                continue;
            }

            $result = $syncService->syncOrder($order, $tracking, $dryRun);
            $detected++;
            if (!empty($result['updated'])) {
                $updated++;
            }

            $this->line(sprintf(
                '%s %s old=%s new=%s status="%s"',
                $dryRun ? 'DRY' : 'SYNC',
                $order->order_code,
                $result['old_status'] ?? '',
                $result['new_status'] ?? '',
                $result['status_text'] ?? ''
            ));
        }

        $this->info("Checked {$orders->count()} orders. Detected {$detected}. Updated {$updated}.");
        return 0;
    }
}
