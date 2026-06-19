<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AnalyzeDuplicateOrdersCommand extends Command
{
    protected $signature = 'orders:analyze-duplicate-orders
        {--cutoff=2025-01-03 16:55:30 : Mốc phân loại đơn cũ/mới}
        {--code= : Chỉ phân tích một mã vận đơn}
        {--export= : Xuất kết quả ra file .xlsx hoặc .csv}';

    protected $description = 'Phân tích các nhóm mã trùng để nhận diện khả năng duplicate cả đơn';

    public function handle()
    {
        $cutoff = Carbon::parse($this->option('cutoff'));
        $code = trim((string) $this->option('code'));
        $exportPath = trim((string) $this->option('export'));
        $rows = [];

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
            $orders = Order::with(['sender', 'receiver', 'services', 'user'])
                ->where('order_code', $duplicate->order_code)
                ->orderBy('id')
                ->get();

            $signatures = $orders->map(function ($order) {
                return $this->buildDuplicateSignature($order);
            });
            $uniqueSignatureCount = $signatures->unique()->count();
            $allSameContent = $uniqueSignatureCount === 1;
            $hasNewOrder = $orders->contains(function ($order) use ($cutoff) {
                return !$order->created_at || $order->created_at->gt($cutoff);
            });
            $hasPartnerCode = $orders->contains(function ($order) {
                return !empty($order->order_partner_code);
            });

            foreach ($orders as $order) {
                $rows[] = [
                    'Nhóm mã trùng' => $duplicate->order_code,
                    'Số đơn trùng' => $duplicate->total,
                    'Phân loại nội dung' => $allSameContent ? 'Nghi duplicate cả đơn' : 'Trùng mã nhưng nội dung khác',
                    'Số mẫu nội dung khác nhau' => $uniqueSignatureCount,
                    'Có đơn sau mốc cutoff' => $hasNewOrder ? 'Có' : 'Không',
                    'Có mã đối tác trong nhóm' => $hasPartnerCode ? 'Có' : 'Không',
                    'Gợi ý xử lý' => $this->suggestAction($allSameContent, $hasNewOrder, $hasPartnerCode),
                    'ID' => $order->id,
                    'Mã vận đơn' => $order->order_code,
                    'Mã tham chiếu' => $order->invoice_code,
                    'Mã đối tác' => $order->order_partner_code,
                    'Đối tác' => $order->partner_code,
                    'Trạng thái giao' => Order::DELIVERY_MAP[$order->delivery_status] ?? $order->delivery_status,
                    'Người tạo' => $order->user ? $order->user->name : '',
                    'User ID' => $order->user_id,
                    'Ngày tạo' => $order->created_at,
                    'Người gửi' => $order->sender ? $order->sender->sender_name : '',
                    'SĐT gửi' => $order->sender ? $order->sender->sender_phone : '',
                    'Địa chỉ gửi' => $order->sender ? $order->sender->address : '',
                    'Người nhận' => $order->receiver ? $order->receiver->receiver_name : '',
                    'SĐT nhận' => $order->receiver ? $order->receiver->receiver_phone : '',
                    'Địa chỉ nhận' => $order->receiver ? $order->receiver->address : '',
                    'Trọng lượng' => $order->weight,
                    'Nội dung' => $order->note,
                    'Tổng tiền' => $order->total,
                    'Thu hộ' => $order->collection,
                ];
            }
        }

        if ($exportPath !== '') {
            $this->exportRows($rows, $exportPath);
            $this->info('Đã xuất file: ' . $exportPath);
        } else {
            $this->table(array_keys($rows[0]), array_slice($rows, 0, 100));
            if (count($rows) > 100) {
                $this->warn('Chỉ hiển thị 100 dòng đầu. Dùng --export để xuất đầy đủ.');
            }
        }

        return 0;
    }

    private function buildDuplicateSignature(Order $order)
    {
        $services = $order->services
            ? $order->services->map(function ($service) {
                return $service->type . ':' . $service->service;
            })->sort()->values()->implode('|')
            : '';

        $parts = [
            $order->user_id,
            $order->order_date,
            $order->payment_method,
            $order->delivery_status,
            $order->weight,
            $order->quantity,
            $order->type,
            $order->total,
            $order->collection,
            $this->normalizeText($order->note),
            $this->normalizeText($order->sender ? $order->sender->sender_name : ''),
            $this->normalizeText($order->sender ? $order->sender->sender_phone : ''),
            $this->normalizeText($order->sender ? $order->sender->address : ''),
            $this->normalizeText($order->receiver ? $order->receiver->receiver_name : ''),
            $this->normalizeText($order->receiver ? $order->receiver->receiver_phone : ''),
            $this->normalizeText($order->receiver ? $order->receiver->address : ''),
            $services,
        ];

        return sha1(json_encode($parts));
    }

    private function normalizeText($value)
    {
        return preg_replace('/\s+/u', ' ', trim(mb_strtolower((string) $value, 'UTF-8')));
    }

    private function suggestAction($allSameContent, $hasNewOrder, $hasPartnerCode)
    {
        if ($hasNewOrder) {
            return 'Kiểm tra thủ công vì có đơn sau mốc cutoff';
        }
        if ($allSameContent && !$hasPartnerCode) {
            return 'Có thể là duplicate đơn cũ - nên giữ 1 bản, bản còn lại cần xem xét xóa/ẩn thay vì đổi mã';
        }
        if ($allSameContent && $hasPartnerCode) {
            return 'Duplicate đơn nhưng có mã đối tác - giữ bản có mã đối tác, kiểm tra bản còn lại thủ công';
        }

        return 'Nội dung khác nhau - có thể đổi mã cho bản trùng sau khi xác nhận';
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
