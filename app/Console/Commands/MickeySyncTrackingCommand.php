<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MickeyService;
use App\Services\MickeyTrackingSyncService;
use Illuminate\Console\Command;

class MickeySyncTrackingCommand extends Command
{
    protected $signature = 'mickey:sync-tracking {--dry-run} {--limit=} {--order-code=}';

    protected $description = 'Sync delivery status for orders already detected as Mickey orders';

    public function handle(MickeyService $mickeyService, MickeyTrackingSyncService $syncService)
    {
        if (!config('tracking.mickey_sync_enabled')) {
            $this->info('Mickey sync is disabled.');
            return 0;
        }

        $dryRun = (bool)$this->option('dry-run');
        $orderCode = trim((string)$this->option('order-code'));
        $limit = (int)($this->option('limit') ?: config('tracking.mickey_sync_limit', 300));

        if ($orderCode !== '') {
            $orders = Order::where('tracking_provider', Order::TRACKING_PROVIDER_MICKEY)
                ->where(function ($query) use ($orderCode) {
                    $query->where('order_code', $orderCode)
                        ->orWhere('invoice_code', $orderCode);
                })
                ->limit(1)
                ->get();
        } else {
            $orders = Order::where('tracking_provider', Order::TRACKING_PROVIDER_MICKEY)
                ->where('delivery_status', '!=', Order::DELIVERY_STATUS_OK)
                ->orderBy('updated_at')
                ->limit($limit)
                ->get();
        }

        $updated = 0;
        $mapped = 0;

        foreach ($orders as $order) {
            $tracking = $mickeyService->tracking($order, $order->order_code);
            if (!$syncService->hasTrackingData($tracking)) {
                $this->line("MISS {$order->order_code}");
                continue;
            }

            $result = $syncService->syncOrder($order, $tracking, $dryRun);
            if (!empty($result['mapped'])) {
                $mapped++;
            }
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

        $this->info("Checked {$orders->count()} Mickey orders. Mapped {$mapped}. Updated {$updated}.");
        return 0;
    }
}
