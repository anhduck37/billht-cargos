<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FindDuplicateOrderCodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:duplicate-codes {--code= : Chỉ kiểm tra một mã vận đơn cụ thể}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Liệt kê các mã vận đơn đang bị trùng, không tự sửa dữ liệu';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $code = trim((string) $this->option('code'));

        $duplicateQuery = Order::query()
            ->select('order_code', DB::raw('COUNT(*) as total'))
            ->whereNotNull('order_code')
            ->where('order_code', '<>', '')
            ->groupBy('order_code')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('order_code');

        if ($code !== '') {
            $duplicateQuery->where('order_code', $code);
        }

        $duplicates = $duplicateQuery->get();

        if ($duplicates->isEmpty()) {
            $this->info($code !== '' ? "Không tìm thấy mã trùng: {$code}" : 'Không có mã vận đơn trùng.');
            return 0;
        }

        foreach ($duplicates as $duplicate) {
            $this->line('');
            $this->warn("Mã trùng: {$duplicate->order_code} ({$duplicate->total} đơn)");

            $orders = Order::with('user')
                ->where('order_code', $duplicate->order_code)
                ->orderBy('id')
                ->get();

            $rows = $orders->map(function ($order) {
                return [
                    'ID' => $order->id,
                    'Mã vận đơn' => $order->order_code,
                    'Mã tham chiếu' => $order->invoice_code,
                    'Người tạo' => $order->user ? $order->user->name : '',
                    'User ID' => $order->user_id,
                    'Ngày tạo' => $order->created_at,
                    'Ngày cập nhật' => $order->updated_at,
                ];
            })->toArray();

            $this->table([
                'ID',
                'Mã vận đơn',
                'Mã tham chiếu',
                'Người tạo',
                'User ID',
                'Ngày tạo',
                'Ngày cập nhật',
            ], $rows);
        }

        return 1;
    }
}
