<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FindDuplicateOrderCodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:duplicate-codes
        {--code= : Chỉ kiểm tra một mã vận đơn cụ thể}
        {--export= : Xuất danh sách ra file .xlsx hoặc .csv}';

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
        $exportPath = trim((string) $this->option('export'));

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

        $exportRows = [];

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
                    'Mã đối tác' => $order->order_partner_code,
                    'Đối tác' => $order->partner_code,
                    'Trạng thái giao' => Order::DELIVERY_MAP[$order->delivery_status] ?? $order->delivery_status,
                    'Người tạo' => $order->user ? $order->user->name : '',
                    'User ID' => $order->user_id,
                    'Ngày tạo' => $order->created_at,
                    'Ngày cập nhật' => $order->updated_at,
                    'Lỗi đồng bộ' => $order->push_error,
                ];
            })->toArray();

            $displayRows = [];
            foreach ($rows as $row) {
                $row = array_merge([
                    'Nhóm mã trùng' => $duplicate->order_code,
                    'Số đơn trùng' => $duplicate->total,
                ], $row);
                $displayRows[] = $row;
                $exportRows[] = $row;
            }

            $this->table([
                'Nhóm mã trùng',
                'Số đơn trùng',
                'ID',
                'Mã vận đơn',
                'Mã tham chiếu',
                'Mã đối tác',
                'Đối tác',
                'Trạng thái giao',
                'Người tạo',
                'User ID',
                'Ngày tạo',
                'Ngày cập nhật',
                'Lỗi đồng bộ',
            ], $displayRows);
        }

        if ($exportPath !== '') {
            $this->exportRows($exportRows, $exportPath);
            $this->info('Đã xuất file: ' . $exportPath);
        }

        return 1;
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
