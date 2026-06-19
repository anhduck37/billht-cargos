<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\OrderTracking;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FixDuplicateOrderCodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:fix-duplicate-codes
        {--cutoff=2025-01-03 16:55:30 : Chỉ tự xử lý nhóm có toàn bộ đơn tạo trước hoặc bằng mốc này}
        {--code= : Chỉ xử lý một mã vận đơn cụ thể}
        {--apply : Chạy thật. Không có option này thì chỉ xem trước}
        {--include-new : Cho phép xử lý cả nhóm có đơn sau mốc cutoff}
        {--export= : Xuất kế hoạch/kết quả ra file .xlsx hoặc .csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xử lý mã vận đơn trùng theo cơ chế an toàn, mặc định chỉ xem trước';

    public function handle()
    {
        $cutoff = Carbon::parse($this->option('cutoff'));
        $code = trim((string) $this->option('code'));
        $apply = (bool) $this->option('apply');
        $includeNew = (bool) $this->option('include-new');
        $exportPath = trim((string) $this->option('export'));
        $rows = [];
        $changed = 0;
        $skippedGroups = 0;

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
            $this->info($code !== '' ? "Không tìm thấy mã trùng: {$code}" : 'Không còn mã vận đơn trùng.');
            return 0;
        }

        foreach ($duplicates as $duplicate) {
            $orders = Order::with('user')
                ->where('order_code', $duplicate->order_code)
                ->orderBy('id')
                ->get();

            $hasNewOrder = $orders->contains(function ($order) use ($cutoff) {
                return !$order->created_at || $order->created_at->gt($cutoff);
            });

            $canFixGroup = !$hasNewOrder || $includeNew;
            if (!$canFixGroup) {
                $skippedGroups++;
            }

            $keepOrder = $this->chooseOrderToKeep($orders);

            foreach ($orders as $order) {
                $action = $order->id == $keepOrder->id ? 'Giữ nguyên' : ($canFixGroup ? 'Đổi mã' : 'Bỏ qua - có đơn sau mốc cutoff');
                $newCode = '';

                if ($apply && $action === 'Đổi mã') {
                    $newCode = $this->changeOrderCode($order);
                    $changed++;
                } elseif ($action === 'Đổi mã') {
                    $newCode = '(sẽ cấp mã mới khi chạy --apply)';
                }

                $rows[] = [
                    'Nhóm mã trùng' => $duplicate->order_code,
                    'Số đơn trùng' => $duplicate->total,
                    'Hành động' => $action,
                    'ID giữ lại' => $keepOrder->id,
                    'ID' => $order->id,
                    'Mã cũ' => $duplicate->order_code,
                    'Mã mới' => $newCode,
                    'Mã tham chiếu' => $order->invoice_code,
                    'Mã đối tác' => $order->order_partner_code,
                    'Đối tác' => $order->partner_code,
                    'Trạng thái giao' => Order::DELIVERY_MAP[$order->delivery_status] ?? $order->delivery_status,
                    'Người tạo' => $order->user ? $order->user->name : '',
                    'User ID' => $order->user_id,
                    'Ngày tạo' => $order->created_at,
                    'Ngày cập nhật' => $order->updated_at,
                ];
            }
        }

        if ($exportPath !== '') {
            $this->exportRows($rows, $exportPath);
            $this->info('Đã xuất file: ' . $exportPath);
        } else {
            $this->table(array_keys($rows[0]), array_slice($rows, 0, 200));
            if (count($rows) > 200) {
                $this->warn('Chỉ hiển thị 200 dòng đầu. Dùng --export để xuất đầy đủ.');
            }
        }

        $this->info($apply ? "Đã đổi mã {$changed} vận đơn." : 'Đây là bản xem trước. Thêm --apply để chạy thật.');
        $this->info("Nhóm bỏ qua do có đơn sau mốc cutoff: {$skippedGroups}");

        return 0;
    }

    private function chooseOrderToKeep($orders)
    {
        $withPartnerCode = $orders->filter(function ($order) {
            return !empty($order->order_partner_code);
        })->sortBy('id')->first();

        return $withPartnerCode ?: $orders->sortBy('id')->first();
    }

    private function changeOrderCode(Order $order)
    {
        return DB::transaction(function () use ($order) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();
            $oldCode = $order->order_code;
            $newCode = app(OrderService::class)->getOrderCode(config('order_manager.prefix_code'));

            $order->order_code = $newCode;
            if (empty($order->invoice_code) || $order->invoice_code === $oldCode) {
                $order->invoice_code = $newCode;
            }
            $order->save();

            OrderTracking::where('order_id', $order->id)->update(['order_code' => $newCode]);

            return $newCode;
        });
    }

    private function exportRows(array $rows, $exportPath)
    {
        $directory = dirname($exportPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = array_keys($rows[0]);

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $columnIndex => $header) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 2, $row[$header]);
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $extension = strtolower(pathinfo($exportPath, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
        } else {
            $writer = new Xlsx($spreadsheet);
        }

        $writer->save($exportPath);
    }
}
