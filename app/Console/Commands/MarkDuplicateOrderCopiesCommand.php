<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\OrderTracking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MarkDuplicateOrderCopiesCommand extends Command
{
    protected $signature = 'orders:mark-duplicate-copies
        {--before=2026-04-01 00:00:00 : Chỉ xử lý nhóm có toàn bộ đơn tạo trước mốc này}
        {--code= : Chỉ xử lý một mã vận đơn}
        {--apply : Chạy thật. Không có option này thì chỉ xem trước}
        {--export= : Xuất kế hoạch/kết quả ra file .xlsx hoặc .csv}';

    protected $description = 'Đánh dấu các bản duplicate cả đơn bằng mã DUP, mặc định chỉ xem trước';

    public function handle()
    {
        $before = Carbon::parse($this->option('before'));
        $code = trim((string) $this->option('code'));
        $apply = (bool) $this->option('apply');
        $exportPath = trim((string) $this->option('export'));
        $rows = [];
        $changed = 0;

        $duplicateQuery = Order::query()
            ->select('order_code', DB::raw('COUNT(*) as total'))
            ->whereNotNull('order_code')
            ->where('order_code', '<>', '')
            ->where('order_code', 'NOT LIKE', 'DUP%')
            ->groupBy('order_code')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('order_code');

        if ($code !== '') {
            $duplicateQuery->where('order_code', $code);
        }

        $duplicates = $duplicateQuery->get();

        if ($duplicates->isEmpty()) {
            $this->info($code !== '' ? "Không tìm thấy mã trùng: {$code}" : 'Không còn mã vận đơn trùng cần kiểm tra.');
            return 0;
        }

        foreach ($duplicates as $duplicate) {
            $orders = Order::with(['sender', 'receiver', 'services', 'user'])
                ->where('order_code', $duplicate->order_code)
                ->orderBy('id')
                ->get();

            $allBefore = $orders->every(function ($order) use ($before) {
                return $order->created_at && $order->created_at->lt($before);
            });
            $allSameContent = $orders->map(function ($order) {
                return $this->buildDuplicateSignature($order);
            })->unique()->count() === 1;

            $keepOrder = $this->chooseOrderToKeep($orders);

            foreach ($orders as $order) {
                $action = 'Bỏ qua';
                $newCode = '';
                $reason = '';

                if (!$allBefore) {
                    $reason = 'Có đơn từ mốc --before trở đi';
                } elseif (!$allSameContent) {
                    $reason = 'Nội dung khác nhau';
                } elseif ($order->id == $keepOrder->id) {
                    $action = 'Giữ nguyên';
                    $reason = 'Bản giữ lại';
                } else {
                    $action = 'Đánh dấu DUP';
                    $reason = 'Bản duplicate cả đơn';
                    $newCode = $this->buildDupCode($order, $duplicate->order_code);

                    if ($apply) {
                        $newCode = $this->markDuplicateCopy($order, $newCode);
                        $changed++;
                    }
                }

                $rows[] = [
                    'Nhóm mã trùng' => $duplicate->order_code,
                    'Số đơn trùng' => $duplicate->total,
                    'Hành động' => $action,
                    'Lý do' => $reason,
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

        $this->info($apply ? "Đã đánh dấu DUP {$changed} vận đơn." : 'Đây là bản xem trước. Thêm --apply để chạy thật.');

        return 0;
    }

    private function chooseOrderToKeep($orders)
    {
        $withPartnerCode = $orders->filter(function ($order) {
            return !empty($order->order_partner_code);
        })->sortBy('id')->first();

        return $withPartnerCode ?: $orders->sortBy('id')->first();
    }

    private function buildDupCode(Order $order, $oldCode)
    {
        return 'DUP' . $order->id . '-' . $oldCode;
    }

    private function markDuplicateCopy(Order $order, $newCode)
    {
        return DB::transaction(function () use ($order, $newCode) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();
            $oldCode = $order->order_code;

            while (Order::where('order_code', $newCode)->where('id', '<>', $order->id)->exists()) {
                $newCode = 'DUP' . $order->id . '-' . uniqid();
            }

            $order->order_code = $newCode;
            if (empty($order->invoice_code) || $order->invoice_code === $oldCode) {
                $order->invoice_code = $newCode;
            }
            $order->save();

            OrderTracking::where('order_id', $order->id)->update(['order_code' => $newCode]);

            return $newCode;
        });
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
