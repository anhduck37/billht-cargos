<?php

namespace App\Services;

use App\Models\Order;
use App\Jobs\SendOrderEmsJob;
use App\Jobs\SendOrderViettelPostJob;
use App\Jobs\SendSMSJob;
use App\OrderHistory;
use App\Partner;
use App\Receiver;
use App\Sender;
use App\Service;
use App\Services\ApiSenderAddressService;
use App\SmartImportBatch;
use App\SmartImportRow;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class SmartImportService
{
    public function createBatchFromFile(UploadedFile $file, $userId)
    {
        $token = uniqid('smart-import-', true);
        $dir = storage_path('app/smart-import');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $fileName = $token . '.' . ($file->getClientOriginalExtension() ?: 'xlsx');
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;
        File::copy($file->getRealPath(), $path);

        $batch = SmartImportBatch::create([
            'token' => $token,
            'user_id' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'preview',
        ]);

        $this->readRows($batch, $path);
        $this->refreshSummary($batch);

        return $batch->fresh('rows');
    }

    public function revalidateRow(SmartImportRow $row, array $data)
    {
        $normalized = $this->normalizeEditableData($data);
        $validation = $this->validateEditableData($normalized);

        $row->update([
            'editable_data' => $normalized,
            'analysis' => $validation['analysis'],
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
            'status' => empty($validation['errors']) ? 'valid' : 'error',
        ]);

        $this->refreshSummary($row->batch);

        return $row->fresh();
    }

    public function importValidRows(SmartImportBatch $batch)
    {
        $imported = 0;
        $orders = [];

        foreach ($batch->rows()->where('status', 'valid')->whereNull('order_id')->orderBy('row_number')->get() as $row) {
            DB::beginTransaction();
            try {
                $order = $this->createOrderFromRow($row);
                $row->update([
                    'order_id' => $order->id,
                    'status' => 'imported',
                    'imported_at' => now(),
                ]);
                $orders[] = $order;
                $imported++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors = $row->errors ?: [];
                $errors[] = 'Lỗi tạo đơn: ' . $e->getMessage();
                $row->update([
                    'status' => 'error',
                    'errors' => $errors,
                ]);
            }
        }

        $this->refreshSummary($batch);

        return compact('imported', 'orders');
    }

    private function readRows(SmartImportBatch $batch, $path)
    {
        if ($this->isHtmlExcelFile($path)) {
            $this->readHtmlRows($batch, $path);
            return;
        }

        $reader = $this->createExcelReader($path);
        $reader->setReadDataOnly(true);
        $readFilter = new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName = '') {
                $allowedColumns = range('A', 'T');
                return $row <= 2000 && in_array($columnAddress, $allowedColumns);
            }
        };
        $reader->setReadFilter($readFilter);

        $spreadsheet = $this->loadSpreadsheet($reader, $path, $readFilter);
        $sheet = $spreadsheet->getActiveSheet();
        if (!$this->sheetLooksLikeImportTemplate($sheet)) {
            throw new \RuntimeException('File Excel không đúng mẫu import đơn hàng. Vui lòng dùng file mẫu import vận đơn của hệ thống.');
        }
        $rowLimit = $sheet->getHighestDataRow();
        $emptyRows = 0;

        foreach (range(2, $rowLimit) as $rowNumber) {
            $raw = $this->readSheetRow($sheet, $rowNumber);
            if ($this->isEmptyRow($raw)) {
                $emptyRows++;
                if ($emptyRows >= 15) {
                    break;
                }
                continue;
            }
            $emptyRows = 0;

            $editable = $this->normalizeEditableData($raw);
            $validation = $this->validateEditableData($editable);

            $this->createPreviewRow($batch, $rowNumber, $raw, $editable, $validation);
        }
    }

    private function isHtmlExcelFile($path)
    {
        $head = strtolower((string) file_get_contents($path, false, null, 0, 256));

        return strpos($head, '<html') !== false || strpos($head, '<table') !== false;
    }

    private function readHtmlRows(SmartImportBatch $batch, $path)
    {
        $html = file_get_contents($path);
        $document = new \DOMDocument();
        $previous = set_error_handler(function () {
            return true;
        });
        $loaded = $document->loadHTML($html);
        restore_error_handler();
        unset($previous);

        if (!$loaded) {
            throw new \RuntimeException('Không đọc được file Excel dạng HTML. Vui lòng lưu lại file dưới dạng .xlsx rồi upload lại.');
        }

        $rows = $document->getElementsByTagName('tr');
        $created = 0;
        $rowNumber = 0;
        $headerFound = false;

        foreach ($rows as $tr) {
            $rowNumber++;
            if ($rowNumber > 2000) {
                continue;
            }

            $cells = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                if (count($cells) >= 20) {
                    break;
                }
                $cells[] = $this->normalizeCellText($td->textContent);
            }

            if (!$headerFound) {
                if ($this->cellsLookLikeImportHeader($cells)) {
                    $headerFound = true;
                }
                continue;
            }

            if (count($cells) < 2) {
                continue;
            }

            $raw = $this->mapCellsToRawData($cells);
            if ($this->isEmptyRow($raw)) {
                continue;
            }

            $editable = $this->normalizeEditableData($raw);
            $validation = $this->validateEditableData($editable);
            $this->createPreviewRow($batch, $rowNumber, $raw, $editable, $validation);
            $created++;
        }

        if (!$headerFound) {
            throw new \RuntimeException('File Excel không đúng mẫu import đơn hàng. Vui lòng dùng file mẫu import vận đơn của hệ thống.');
        }

        if ($created === 0) {
            throw new \RuntimeException('File không có dữ liệu đơn hàng để import. Vui lòng kiểm tra lại file mẫu.');
        }
    }

    private function normalizeCellText($value)
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    private function mapCellsToRawData(array $cells)
    {
        $cells = array_pad($cells, 20, '');

        return [
            'order_date' => $cells[0],
            'sender_name' => $cells[1],
            'sender_phone' => $cells[2],
            'department' => $cells[3],
            'receiver_name' => $cells[4],
            'receiver_address' => $cells[5],
            'receiver_phone' => $cells[6],
            'payment_method' => $cells[7],
            'weight' => $cells[8],
            'service_domestic' => $cells[9],
            'service_extra' => $cells[10],
            'note' => $cells[11],
            'invoice_code' => $cells[12],
            'person_charge' => $cells[13],
            'quantity' => $cells[14],
            'type' => $cells[15],
            'total' => $cells[16],
            'collection' => $cells[17],
            'sender_address' => $cells[18],
            'partner_code' => $cells[19],
        ];
    }

    private function sheetLooksLikeImportTemplate($sheet)
    {
        for ($row = 1; $row <= 10; $row++) {
            $cells = [];
            foreach (range('A', 'T') as $column) {
                $cells[] = $this->normalizeCellText($sheet->getCell($column . $row)->getValue());
            }

            if ($this->cellsLookLikeImportHeader($cells)) {
                return true;
            }
        }

        return false;
    }

    private function cellsLookLikeImportHeader(array $cells)
    {
        $text = implode(' | ', $cells);

        return strpos($text, 'Tên người gửi') !== false
            && strpos($text, 'Tên người nhận') !== false
            && strpos($text, 'Địa chỉ người nhận') !== false;
    }

    private function createPreviewRow(SmartImportBatch $batch, $rowNumber, array $raw, array $editable, array $validation)
    {
        SmartImportRow::create([
            'smart_import_batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'raw_data' => $raw,
            'editable_data' => $editable,
            'analysis' => $validation['analysis'],
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
            'status' => empty($validation['errors']) ? 'valid' : 'error',
        ]);
    }

    private function createExcelReader($path)
    {
        $signature = strtoupper(bin2hex((string) file_get_contents($path, false, null, 0, 8)));

        if (strpos($signature, '504B0304') === 0) {
            return IOFactory::createReader('Xlsx');
        }

        if (strpos($signature, 'D0CF11E0A1B11AE1') === 0) {
            return IOFactory::createReader('Xls');
        }

        return IOFactory::createReaderForFile($path);
    }

    private function loadSpreadsheet(IReader $reader, $path, \PhpOffice\PhpSpreadsheet\Reader\IReadFilter $readFilter)
    {
        $errors = [];
        $readers = [
            get_class($reader) => $reader,
            'Xlsx' => IOFactory::createReader('Xlsx'),
            'Xls' => IOFactory::createReader('Xls'),
            'Html' => IOFactory::createReader('Html'),
        ];

        foreach ($readers as $name => $currentReader) {
            try {
                $currentReader->setReadDataOnly(true);
                if (method_exists($currentReader, 'setReadFilter')) {
                    $currentReader->setReadFilter($readFilter);
                }
                return $this->loadReaderQuietly($currentReader, $path, $name);
            } catch (\Exception $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }

        throw new \RuntimeException('Không đọc được file Excel. Vui lòng thử lưu lại file dưới dạng .xlsx rồi upload lại. Chi tiết: ' . implode(' | ', $errors));
    }

    private function loadReaderQuietly(IReader $reader, $path, $readerName)
    {
        if ($readerName === 'Html') {
            set_error_handler(function ($severity, $message) {
                if ($severity === E_WARNING && strpos($message, 'DOMDocument::loadHTML()') !== false) {
                    return true;
                }

                return false;
            });
        }

        try {
            return $reader->load($path);
        } finally {
            if ($readerName === 'Html') {
                restore_error_handler();
            }
        }
    }

    private function readSheetRow($sheet, $row)
    {
        return [
            'order_date' => $sheet->getCell('A' . $row)->getValue(),
            'sender_name' => $sheet->getCell('B' . $row)->getCalculatedValue(),
            'sender_phone' => $sheet->getCell('C' . $row)->getCalculatedValue(),
            'department' => $sheet->getCell('D' . $row)->getValue(),
            'receiver_name' => $sheet->getCell('E' . $row)->getCalculatedValue(),
            'receiver_address' => $sheet->getCell('F' . $row)->getCalculatedValue(),
            'receiver_phone' => $sheet->getCell('G' . $row)->getCalculatedValue(),
            'payment_method' => $sheet->getCell('H' . $row)->getValue(),
            'weight' => $sheet->getCell('I' . $row)->getValue(),
            'service_domestic' => $sheet->getCell('J' . $row)->getValue(),
            'service_extra' => $sheet->getCell('K' . $row)->getValue(),
            'note' => $sheet->getCell('L' . $row)->getValue(),
            'invoice_code' => $sheet->getCell('M' . $row)->getCalculatedValue(),
            'person_charge' => $sheet->getCell('N' . $row)->getValue(),
            'quantity' => $sheet->getCell('O' . $row)->getValue(),
            'type' => $sheet->getCell('P' . $row)->getValue(),
            'total' => $sheet->getCell('Q' . $row)->getValue(),
            'collection' => $sheet->getCell('R' . $row)->getValue(),
            'sender_address' => $sheet->getCell('S' . $row)->getCalculatedValue(),
            'partner_code' => $sheet->getCell('T' . $row)->getCalculatedValue(),
        ];
    }

    private function normalizeEditableData(array $data)
    {
        return [
            'order_date' => trim((string)($data['order_date'] ?? '')),
            'sender_name' => trim((string)($data['sender_name'] ?? '')),
            'sender_phone' => trim((string)($data['sender_phone'] ?? '')),
            'department' => trim((string)($data['department'] ?? '')),
            'receiver_name' => trim((string)($data['receiver_name'] ?? '')),
            'receiver_address' => trim((string)($data['receiver_address'] ?? '')),
            'receiver_phone' => trim((string)($data['receiver_phone'] ?? '')),
            'payment_method' => trim((string)($data['payment_method'] ?? '')),
            'weight' => trim((string)($data['weight'] ?? '')),
            'service_domestic' => trim((string)($data['service_domestic'] ?? '')),
            'service_extra' => trim((string)($data['service_extra'] ?? '')),
            'note' => trim((string)($data['note'] ?? '')),
            'invoice_code' => trim((string)($data['invoice_code'] ?? '')),
            'person_charge' => trim((string)($data['person_charge'] ?? '')),
            'quantity' => trim((string)($data['quantity'] ?? '')),
            'type' => trim((string)($data['type'] ?? '')),
            'total' => trim((string)($data['total'] ?? '')),
            'collection' => trim((string)($data['collection'] ?? '')),
            'sender_address' => trim((string)($data['sender_address'] ?? '')),
            'partner_code' => $this->normalizePartnerCode($data['partner_code'] ?? ''),
        ];
    }

    private function validateEditableData(array $data)
    {
        $errors = [];
        $warnings = [];
        $analysis = [
            'sender' => $this->analyzeAddress($data['sender_address']),
            'receiver' => $this->analyzeAddress($data['receiver_address']),
        ];

        foreach ([
            'sender_name' => 'Thiếu tên người gửi',
            'sender_phone' => 'Thiếu SĐT người gửi',
            'sender_address' => 'Thiếu địa chỉ người gửi',
            'receiver_name' => 'Thiếu tên người nhận',
            'receiver_phone' => 'Thiếu SĐT người nhận',
            'receiver_address' => 'Thiếu địa chỉ người nhận',
            'note' => 'Thiếu nội dung hàng hóa',
        ] as $field => $message) {
            if (($data[$field] ?? '') === '') {
                $errors[] = $message;
            }
        }

        if ($data['partner_code'] === Order::CODE_VIETTEL_POST && $data['service_domestic'] === '') {
            $errors[] = 'Thiếu Dịch vụ trong nước để đẩy Viettel';
        }

        if (!empty($data['service_domestic']) && !app(OrderService::class)->getKeyService($data['service_domestic'])) {
            $warnings[] = 'Dịch vụ trong nước chưa nhận diện được';
        }

        foreach (['sender' => 'người gửi', 'receiver' => 'người nhận'] as $key => $label) {
            if (!$analysis[$key]['parsed']) {
                $errors[] = 'Địa chỉ ' . $label . ' chưa nhận diện được tỉnh/xã hoặc tỉnh/huyện/xã';
            }
            if ($data['partner_code'] === Order::CODE_VIETTEL_POST && !$analysis[$key]['vtp_ready']) {
                $errors[] = 'Địa chỉ ' . $label . ' chưa đủ mapping VTP';
            }
            if ($data['partner_code'] === Order::CODE_EMS && !$analysis[$key]['ems_ready']) {
                $errors[] = 'Địa chỉ ' . $label . ' chưa đủ mapping EMS';
            }
        }

        return compact('errors', 'warnings', 'analysis');
    }

    private function analyzeAddress($address)
    {
        $address = trim((string)$address);
        $result = [
            'parsed' => false,
            'scheme' => null,
            'detail_address' => $address,
            'display_address' => $address,
            'new_province_id' => null,
            'new_ward_id' => null,
            'province_name' => null,
            'district_name' => null,
            'ward_name' => null,
            'city_id' => null,
            'district_id' => null,
            'ward_id' => null,
            'vtp_codes' => null,
            'ems_codes' => null,
            'vtp_ready' => false,
            'ems_ready' => false,
        ];

        if ($address === '') {
            return $result;
        }

        $addressService = app(Address2025Service::class);
        $newParsed = $addressService->parseFullAddress($address);
        if (!empty($newParsed['success'])) {
            $newProvince = !empty($newParsed['new_province_id']) ? \App\NewProvince::find($newParsed['new_province_id']) : null;
            $newWard = !empty($newParsed['new_ward_id']) ? \App\NewWard::find($newParsed['new_ward_id']) : null;
            $result['parsed'] = true;
            $result['scheme'] = 'new';
            $result['detail_address'] = $newParsed['address'];
            $result['new_province_id'] = $newParsed['new_province_id'];
            $result['new_ward_id'] = $newParsed['new_ward_id'];
            $result['province_name'] = $newParsed['province_name'] ?? optional($newProvince)->name;
            $result['ward_name'] = $newParsed['ward_name'] ?? optional($newWard)->name;
            $result['display_address'] = trim(($newParsed['address'] ?? '') . ', ' . ($result['ward_name'] ?? '') . ', ' . ($result['province_name'] ?? ''), ' ,');
            $vtpMapping = $this->getPartnerMapping($newParsed['new_ward_id'], 'VTP');
            $emsMapping = $this->getPartnerMapping($newParsed['new_ward_id'], 'EMS');
            $result['vtp_codes'] = $this->formatMappingCodes($vtpMapping);
            $result['ems_codes'] = $this->formatMappingCodes($emsMapping);
            $result['vtp_ready'] = $this->mappingIsComplete($vtpMapping);
            $result['ems_ready'] = $this->mappingIsComplete($emsMapping);
            return $result;
        }

        $legacyParsed = $this->parseLegacyAddressToIds($address);
        if (!empty($legacyParsed['city_id']) && !empty($legacyParsed['district_id']) && !empty($legacyParsed['ward_id'])) {
            $city = \App\City::find($legacyParsed['city_id']);
            $district = \App\District::find($legacyParsed['district_id']);
            $ward = \App\Ward::find($legacyParsed['ward_id']);
            $result['parsed'] = true;
            $result['scheme'] = 'old';
            $result['detail_address'] = $legacyParsed['address'];
            $result['city_id'] = $legacyParsed['city_id'];
            $result['district_id'] = $legacyParsed['district_id'];
            $result['ward_id'] = $legacyParsed['ward_id'];
            $result['province_name'] = optional($city)->city_name;
            $result['district_name'] = optional($district)->district_name;
            $result['ward_name'] = optional($ward)->ward_name;
            $result['display_address'] = trim(($legacyParsed['address'] ?? '') . ', ' . optional($ward)->ward_name . ', ' . optional($district)->district_name . ', ' . optional($city)->city_name, ' ,');
            $result['vtp_codes'] = [
                'province' => optional($city)->city_code,
                'district' => optional($district)->district_code,
                'ward' => optional($ward)->ward_code,
            ];
            $result['ems_codes'] = [
                'province' => optional($city)->ems_code,
                'district' => optional($district)->ems_code,
                'ward' => optional($ward)->ems_code,
            ];
            $result['vtp_ready'] = !empty(optional($city)->city_code) && !empty(optional($district)->district_code) && !empty(optional($ward)->ward_code);
            $result['ems_ready'] = !empty(optional($city)->ems_code) && !empty(optional($district)->ems_code) && !empty(optional($ward)->ems_code);
        }

        return $result;
    }

    private function hasPartnerMapping($newWardId, $partnerCode)
    {
        return $this->mappingIsComplete($this->getPartnerMapping($newWardId, $partnerCode));
    }

    private function getPartnerMapping($newWardId, $partnerCode)
    {
        if (!$newWardId) {
            return null;
        }

        return app(Address2025Service::class)->getPartnerMapping($newWardId, $partnerCode);
    }

    private function mappingIsComplete($mapping)
    {
        return $mapping
            && !empty($mapping->partner_province_code)
            && !empty($mapping->partner_district_code)
            && !empty($mapping->partner_ward_code);
    }

    private function formatMappingCodes($mapping)
    {
        if (!$mapping) {
            return null;
        }

        return [
            'province' => $mapping->partner_province_code,
            'district' => $mapping->partner_district_code,
            'ward' => $mapping->partner_ward_code,
        ];
    }

    private function createOrderFromRow(SmartImportRow $row)
    {
        $data = $row->editable_data;
        $senderData = $this->buildAddressData('sender', $data['sender_address']);
        $senderData['sender_name'] = $data['sender_name'];
        $senderData['sender_phone'] = $data['sender_phone'];

        $receiverData = $this->buildAddressData('receiver', $data['receiver_address']);
        $receiverData['receiver_name'] = $data['receiver_name'];
        $receiverData['receiver_phone'] = $data['receiver_phone'];

        $sender = Sender::create($senderData);
        $receiver = Receiver::create($receiverData);

        $orderData = [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'order_date' => $this->normalizeOrderDate($data['order_date']),
            'department' => $data['department'],
            'weight' => $data['weight'] !== '' ? $data['weight'] : 0,
            'note' => $data['note'],
            'user_id' => auth()->user()->id,
            'order_status' => Order::ORDER_BLANK,
            'delivery_status' => Order::DELIVERY_STATUS_BLANK,
            'quantity' => $data['quantity'] !== '' ? $data['quantity'] : 1,
            'total' => (int)($data['total'] ?: 0),
            'collection' => (int)($data['collection'] ?: 0),
            'order_code' => app(OrderService::class)->getOrderCode(config('order_manager.prefix_code')),
            'address_scheme' => $senderData['address_scheme'] === 'new' || $receiverData['address_scheme'] === 'new' ? 'new' : 'old',
        ];

        if ($data['payment_method'] !== '') {
            $orderData['payment_method'] = app(OrderService::class)->getKeyPaymentMethod($data['payment_method']);
        }
        if ($data['type'] !== '') {
            $orderData['type'] = app(OrderService::class)->getType($data['type']);
        }
        if ($data['invoice_code'] !== '') {
            $existing = Order::where('order_code', $data['invoice_code'])->first();
            $orderData['invoice_code'] = $existing ? $orderData['order_code'] : $data['invoice_code'];
            if (!$existing) {
                $orderData['order_code'] = $data['invoice_code'];
            }
        }
        if ($data['person_charge'] !== '' && in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            $person = User::where('name', 'LIKE', '%' . $data['person_charge'] . '%')->first();
            $orderData['person_charge'] = $person ? $person->id : 0;
        }

        $order = Order::create($orderData);
        app(OrderTrackingService::class)->create($order, []);
        app(OrderHistoryService::class)->createOrderHistory(null, $order, null, OrderHistory::NOT_TOTAL_ORDER, OrderHistory::TYPE_ORDER_CREATE, 'SMART_IMPORT', ['action_desc' => 'Tạo mới vận đơn qua Import thông minh']);

        $this->insertServices($order, $data);

        if (!empty($order->receiver->receiver_phone)) {
            dispatch(new SendSMSJob($order));
        }

        if ($data['partner_code'] === Order::CODE_VIETTEL_POST) {
            $serviceViettel = null;
            if ($data['service_domestic'] !== '') {
                $info = app(OrderService::class)->getKeyService($data['service_domestic']);
                $serviceViettel = Service::VIETTEL_POST_SERVICE[$info['service_key'] ?? null] ?? null;
            }
            app(ApiSenderAddressService::class)->ensureDefaultSenderAddressForApi($order, Order::CODE_VIETTEL_POST);
            dispatch(new SendOrderViettelPostJob($order, $serviceViettel));
        } elseif ($data['partner_code'] === Order::CODE_EMS) {
            app(ApiSenderAddressService::class)->ensureDefaultSenderAddressForApi($order, Order::CODE_EMS);
            dispatch(new SendOrderEmsJob($order));
        }

        return $order;
    }

    private function buildAddressData($type, $address)
    {
        $analysis = $this->analyzeAddress($address);
        $data = [
            'address' => $analysis['detail_address'],
            'address_scheme' => $analysis['scheme'] ?: 'old',
        ];

        if ($analysis['scheme'] === 'new') {
            $data['new_province_id'] = $analysis['new_province_id'];
            $data['new_ward_id'] = $analysis['new_ward_id'];
        } else {
            $data['city_id'] = $analysis['city_id'];
            $data['district_id'] = $analysis['district_id'];
            $data['ward_id'] = $analysis['ward_id'];
        }

        return $data;
    }

    private function insertServices(Order $order, array $data)
    {
        $serviceData = [];
        if ($data['service_domestic'] !== '') {
            $info = app(OrderService::class)->getKeyService($data['service_domestic']);
            if ($info && isset($info['type'], $info['service_key'])) {
                $serviceData[$info['type']][] = $info['service_key'];
            }
        }
        if ($data['service_extra'] !== '') {
            foreach (explode(',', $data['service_extra']) as $serviceName) {
                $info = app(OrderService::class)->getKeyService(trim($serviceName));
                if ($info && isset($info['type'], $info['service_key'])) {
                    $serviceData[$info['type']][] = $info['service_key'];
                }
            }
        }
        if (!empty($serviceData)) {
            app(OrderService::class)->insertService($serviceData, $order->id);
        }
    }

    private function normalizeOrderDate($value)
    {
        if ($value === '' || $value === null) {
            return date('Y-m-d');
        }
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int)$value)->format('Y-m-d');
        }
        $parts = explode('/', (string)$value);
        if (count($parts) >= 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return date('Y-m-d', strtotime((string)$value));
    }

    private function isEmptyRow(array $row)
    {
        foreach (['sender_name', 'sender_phone', 'receiver_name', 'receiver_address', 'receiver_phone'] as $key) {
            if (trim((string)($row[$key] ?? '')) !== '') {
                return false;
            }
        }
        return true;
    }

    private function normalizePartnerCode($value)
    {
        $value = strtoupper(trim((string)$value));
        if (in_array($value, ['VTP', 'VIETTEL', 'VIETTEL_POST'], true)) {
            return Order::CODE_VIETTEL_POST;
        }
        if ($value === 'EMS') {
            return Order::CODE_EMS;
        }
        return $value;
    }

    private function parseLegacyAddressToIds($address)
    {
        $result = [
            'city_id' => null,
            'district_id' => null,
            'ward_id' => null,
            'address' => trim((string)$address),
        ];

        $normalized = $this->normalizeAddressName($address);
        if ($normalized === '') {
            return $result;
        }

        $city = \App\City::all()->first(function ($city) use ($normalized) {
            return strpos($normalized, $this->normalizeAddressName($city->city_name)) !== false;
        });
        if (!$city) {
            return $result;
        }
        $result['city_id'] = $city->id;

        $district = \App\District::where('city_id', $city->id)->get()->first(function ($district) use ($normalized) {
            return strpos($normalized, $this->normalizeAddressName($district->district_name)) !== false;
        });
        if (!$district) {
            return $result;
        }
        $result['district_id'] = $district->id;

        $ward = \App\Ward::where('district_id', $district->id)->get()->first(function ($ward) use ($normalized) {
            return strpos($normalized, $this->normalizeAddressName($ward->ward_name)) !== false;
        });
        if (!$ward) {
            return $result;
        }
        $result['ward_id'] = $ward->id;

        $detail = (string)$address;
        foreach ([$city->city_name, $district->district_name, $ward->ward_name] as $name) {
            $detail = preg_replace('/(?:^|[\s,]+)' . preg_quote($name, '/') . '(?=$|[\s,]+)/iu', ' ', $detail);
        }
        $result['address'] = trim(preg_replace('/\s+/', ' ', trim($detail, " \t\n\r\0\x0B,")));

        return $result;
    }

    private function normalizeAddressName($value)
    {
        $value = (string)$value;
        if (class_exists('\Illuminate\Support\Str')) {
            $value = \Illuminate\Support\Str::ascii($value);
        }
        $value = strtolower($value);
        $value = preg_replace('/\b(tinh|thanh pho|tp|quan|huyen|thi xa|xa|phuong|thi tran)\b/u', '', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function refreshSummary(SmartImportBatch $batch)
    {
        $summary = [
            'total' => $batch->rows()->count(),
            'valid' => $batch->rows()->where('status', 'valid')->count(),
            'error' => $batch->rows()->where('status', 'error')->count(),
            'imported' => $batch->rows()->where('status', 'imported')->count(),
            'not_printed' => $batch->rows()->whereNotNull('order_id')->count(),
        ];

        $batch->update([
            'total_rows' => $summary['total'],
            'valid_rows' => $summary['valid'],
            'error_rows' => $summary['error'],
            'imported_rows' => $summary['imported'],
            'summary' => $summary,
            'status' => $summary['error'] > 0 ? 'preview' : ($summary['imported'] > 0 ? 'imported' : 'preview'),
        ]);
    }
}
