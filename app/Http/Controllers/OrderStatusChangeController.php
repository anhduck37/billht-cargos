<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderTracking;
use App\Receiver;
use App\Sender;
use App\User;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderStatusChangeController extends Controller
{
    public function index()
    {
        $this->authorizeRole();
        
        $provinces = $this->getProvincesList();

        return view('order_status_changes.index', [
            'importResult' => null,
            'provinces' => $provinces,
        ]);
    }

    public function import(Request $request)
    {
        $this->authorizeRole();
        $file = $request->file('file');
        if (!$file) {
            Flash::error('Không có file');
            return back();
        }

        $mimes = [
            'application/vnd.ms-excel',
            'text/xls',
            'text/xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        if (!in_array($_FILES['file']['type'], $mimes)) {
            Flash::error('File đã chọn phải là excel');
            return back();
        }

        $statusMap = $this->loadStatusMap();
        $result = [
            'success' => 0,
            'skipped' => 0,
            'errors' => [],
            'successBills' => [],
        ];
        
        $importId = uniqid('import-');
        $logFile = storage_path("logs/order-status-change-import-" . date('Y-m-d') . ".log");
        $processedBills = [];

        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rowLimit = $sheet->getHighestDataRow();

        for ($row = 2; $row <= $rowLimit; $row++) {
            $billCode = trim((string) $sheet->getCell('B' . $row)->getValue());
            $billType = trim((string) $sheet->getCell('C' . $row)->getValue());
            $destination = trim((string) $sheet->getCell('D' . $row)->getValue());
            $phone = trim((string) $sheet->getCell('E' . $row)->getValue());
            $signator = trim((string) $sheet->getCell('F' . $row)->getValue());
            $rawDate = $sheet->getCell('A' . $row)->getValue();

            if ($billCode === '' && $destination === '' && $phone === '' && $signator === '') {
                continue;
            }

            if ($billCode === '') {
                $result['skipped']++;
                $result['errors'][] = "Dòng {$row}: thiếu Mã bill.";
                continue;
            }

            if (isset($processedBills[$billCode])) {
                $result['skipped']++;
                $result['errors'][] = "Dòng {$row}, bill {$billCode}: Trùng mã vận đơn (lần đầu xuất hiện tại dòng {$processedBills[$billCode]}).";
                continue;
            }
            $processedBills[$billCode] = $row;

            $statusKey = mb_strtoupper($destination);
            if (!array_key_exists($statusKey, $statusMap)) {
                $result['skipped']++;
                $result['errors'][] = "Dòng {$row}, bill {$billCode}: Tỉnh đến không tồn tại trong data_status.txt.";
                continue;
            }

            $trackingAt = $this->parseTrackingDate($rawDate);
            if (!$trackingAt) {
                $result['skipped']++;
                $result['errors'][] = "Dòng {$row}, bill {$billCode}: Ngày tháng không hợp lệ.";
                continue;
            }

            $statusText = $statusMap[$statusKey];
            if ($phone !== '') {
                $statusText .= ', Số điện thoại ' . $phone;
            }

            DB::beginTransaction();
            try {
                $order = Order::where('order_code', $billCode)->first();
                
                $isBillTypeCreateNew = mb_strtoupper($billType) === 'TẠO MỚI' || mb_strtoupper($billType) === 'TAO MOI';
                
                if (!$order) {
                    // Bill chưa tồn tại
                    if (!$isBillTypeCreateNew) {
                        $result['skipped']++;
                        $result['errors'][] = "Dòng {$row}, bill {$billCode}: bill chưa tồn tại và Loại bill không phải 'Tạo mới'.";
                        DB::rollBack();
                        continue;
                    }
                    // Bill chưa tồn tại + Loại bill = "Tạo mới" → OK, tạo mới
                    $order = $this->createMinimalOrder($billCode);
                } else {
                    // Bill đã tồn tại
                    if ($isBillTypeCreateNew) {
                        $result['skipped']++;
                        $result['errors'][] = "Dòng {$row}, bill {$billCode}: Bill đã tồn tại trên hệ thống, không thể tạo mới.";
                        DB::rollBack();
                        continue;
                    }
                    // Bill đã tồn tại + Loại bill != "Tạo mới" → OK, cập nhật trạng thái
                }

                $order->delivery_status = Order::DELIVERY_STATUS_RECEIVED;
                $order->signator = $signator;
                $order->updated_at = $trackingAt;
                $order->save();

                $tracking = new OrderTracking([
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_status' => $order->order_status,
                    'user_id' => auth()->id(),
                    'delivery_status' => Order::DELIVERY_STATUS_RECEIVED,
                    'city_id' => $order->city_id,
                    'person_charge' => $order->person_charge,
                    'signator' => $signator,
                    'status_text' => $statusText,
                ]);
                $tracking->created_at = $trackingAt;
                $tracking->updated_at = $trackingAt;
                $tracking->save();

                DB::commit();
                $result['success']++;
                $result['successBills'][] = $billCode;
            } catch (\Exception $e) {
                DB::rollBack();
                $result['skipped']++;
                $result['errors'][] = "Dòng {$row}, bill {$billCode}: " . $e->getMessage();
            }
        }
        
        // Log import results
        $this->logImportResult($result, $logFile);

        Flash::success('Đã xử lý import đổi trạng thái.');
        $provinces = $this->getProvincesList();
        return view('order_status_changes.index', [
            'importResult' => $result,
            'provinces' => $provinces,
        ]);
    }

    public function template()
    {
        $this->authorizeRole();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Ngày tháng');
        $sheet->setCellValue('B1', 'Mã bill');
        $sheet->setCellValue('C1', 'Loại bill');
        $sheet->setCellValue('D1', 'Tỉnh đến');
        $sheet->setCellValue('E1', 'Số điện thoại');
        $sheet->setCellValue('F1', 'Ký nhận');

        $sheet->setCellValue('A2', '05-12-2026');
        $sheet->setCellValue('B2', 'HE000001');
        $sheet->setCellValue('C2', '');
        $sheet->setCellValue('D2', 'HẢI PHÒNG');
        $sheet->setCellValue('E2', '0984601593');
        $sheet->setCellValue('F2', 'Nguyễn Văn A');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'mau_doi_trang_thai.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'status_template_');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function authorizeRole()
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }
    }

    private function loadStatusMap()
    {
        $content = file_get_contents(base_path('data_status.txt'));
        $lines = preg_split('/\r\n|\r|\n/', (string) $content);
        $map = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\|/', $line);
            if (count($parts) < 2) {
                continue;
            }
            $key = mb_strtoupper(trim($parts[0]));
            $value = trim($parts[1]);
            $map[$key] = $value;
        }
        return $map;
    }

    private function getProvincesList()
    {
        $statusMap = $this->loadStatusMap();
        return array_keys($statusMap);
    }

    private function parseTrackingDate($rawDate)
    {
        if ($rawDate === null || trim((string) $rawDate) === '') {
            return Carbon::now();
        }

        if (is_numeric($rawDate)) {
            $timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((int) $rawDate);
            return Carbon::createFromTimestamp($timestamp);
        }

        $value = trim((string) $rawDate);
        $formats = ['m-d-Y', 'm/d/Y', 'd-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Exception $e) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function createMinimalOrder($billCode)
    {
        $sender = Sender::create([
            'sender_name' => '',
            'sender_phone' => '',
            'address' => '',
        ]);
        $receiver = Receiver::create([
            'receiver_name' => '',
            'receiver_phone' => '',
            'address' => '',
        ]);

        return Order::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'order_status' => Order::ORDER_BLANK,
            'delivery_status' => Order::DELIVERY_STATUS_RECEIVED,
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'order_code' => $billCode,
            'total' => 0,
            'note' => '',
            'user_id' => auth()->id(),
            'order_date' => date('Y-m-d'),
            'signator' => '',
        ]);
    }

    private function logImportResult($result, $logFile)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logContent = "=== Import Đổi Trạng Thái - {$timestamp} ===\n";
        $logContent .= "Thành công: {$result['success']}\n";
        $logContent .= "Bỏ qua: {$result['skipped']}\n";
        
        if (!empty($result['successBills'])) {
            $logContent .= "\n--- Danh sách mã bill thành công ---\n";
            foreach ($result['successBills'] as $billCode) {
                $logContent .= "✓ {$billCode}\n";
            }
        }
        
        if (!empty($result['errors'])) {
            $logContent .= "\n--- Danh sách lỗi ---\n";
            foreach ($result['errors'] as $error) {
                $logContent .= "✗ {$error}\n";
            }
        }
        
        $logContent .= "\n" . str_repeat("=", 50) . "\n\n";
        
        file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
        Log::info("Import đổi trạng thái hoàn thành. Thành công: {$result['success']}, Bỏ qua: {$result['skipped']}");
    }
}
