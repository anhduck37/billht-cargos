<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendOrderEmsJob;
use App\Jobs\SendOrderViettelPostJob;
use App\City;
use App\District;
use App\Models\Order;
use App\OrderPartnerLog;
use App\PartnerConfig;
use App\OrderHistory;
use App\Ward;
use App\Services\EmsService;
use App\Services\ApiStatusService;
use App\Services\OrderHistoryService;
use App\Services\ViettelPostService;
use App\User;
use Carbon\Carbon;
use Flash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class OrderPartnerLogController extends Controller
{
    public function index(Request $request)
    {
        // Query builder for filtering
        $query = OrderPartnerLog::with([
            'order.receiver.city',
            'order.receiver.district',
            'order.receiver.ward',
            'order.receiver.newProvince',
            'order.receiver.newWard',
        ]);

        // Filter by Status
        if ($request->filled('filter_status')) {
            $query->where('status', $request->input('filter_status'));
        }

        // Filter by Date
        $filterDateInput = $request->filled('filter_date') ? $request->input('filter_date') : $request->input('filter_date_display');
        if ($filterDateInput !== null && trim((string)$filterDateInput) !== '') {
            try {
                $filterDate = $this->parseFilterDate($filterDateInput);
                $query->whereDate('updated_at', $filterDate->toDateString());
            } catch (\Exception $e) {
                Flash::error('Ngày lọc không hợp lệ.');
            }
        }

        // Filter by Partner
        if ($request->filled('filter_partner')) {
            $query->where('partner_code', strtoupper($request->input('filter_partner')));
        }

        // Filter by order code or sender name
        if ($request->filled('filter_search')) {
            $keyword = trim((string)$request->input('filter_search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('payload', 'LIKE', '%' . $keyword . '%')
                    ->orWhereHas('order', function ($orderQuery) use ($keyword) {
                        $orderQuery->where('order_code', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('invoice_code', 'LIKE', '%' . $keyword . '%')
                            ->orWhereHas('sender', function ($senderQuery) use ($keyword) {
                                $senderQuery->where('sender_name', 'LIKE', '%' . $keyword . '%');
                            });
                    });
            });
        }

        // Paginate results
        $logs = $query->orderBy('updated_at', 'desc')->paginate(50);
        
        // Count statistics for the current month
        $currentMonth = Carbon::now()->format('Y-m');
        $successQuery = OrderPartnerLog::where('status', 1)
                                      ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$currentMonth]);
                                      
        $errorQuery = OrderPartnerLog::where('status', 0)
                                    ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$currentMonth]);

        if ($request->filled('filter_partner')) {
            $successQuery->where('partner_code', strtoupper($request->input('filter_partner')));
            $errorQuery->where('partner_code', strtoupper($request->input('filter_partner')));
        }

        $successCount = $successQuery->count();
        $errorCount = $errorQuery->count();

        $orderIds = $logs->getCollection()->pluck('order_id')->filter()->unique()->values()->all();
        $cancelledAtByOrderId = $this->getCancelledAtByOrderId($orderIds);

        // Process the payloads before sending to the view
        foreach ($logs as $log) {
            $cancelledAt = $cancelledAtByOrderId[$log->order_id] ?? null;
            $isCancelledSync = $cancelledAt && $log->updated_at <= $cancelledAt;
            $log->parsed = $this->parseLogData($log, $isCancelledSync);
            $log->can_cancel = $this->canCancel($log);
        }

        $apiStatuses = app(ApiStatusService::class)->getStatuses();
        $logDates = OrderPartnerLog::selectRaw('DATE(updated_at) as log_date')
            ->whereNotNull('updated_at')
            ->groupBy('log_date')
            ->orderBy('log_date', 'desc')
            ->limit(90)
            ->pluck('log_date');

        return view('order_partner_logs.index', compact('logs', 'successCount', 'errorCount', 'apiStatuses', 'logDates'));
    }

    private function formatAppReceiverAddress($order)
    {
        if (!$order || !$order->receiver) {
            return 'N/A';
        }

        if (!empty($order->receiver->full_address_text)) {
            return $order->receiver->full_address_text;
        }

        $parts = [
            $order->receiver->address ?? null,
            $order->receiver->ward_name ?? null,
            $order->receiver->district_name ?? null,
            $order->receiver->city_name ?? null,
        ];

        return implode(', ', array_filter($parts)) ?: 'N/A';
    }

    private function formatPartnerReceiverAddress($log, array $payload)
    {
        $partnerCode = strtoupper((string)$log->partner_code);

        if ($partnerCode === 'VIETTEL_POST') {
            $address = trim((string)($payload['RECEIVER_ADDRESS'] ?? ''));
            $wardName = $this->findWardNameByCode($payload['RECEIVER_WARD'] ?? null);
            $districtName = $this->findDistrictNameByCode($payload['RECEIVER_DISTRICT'] ?? null);
            $provinceName = $this->findCityNameByCode($payload['RECEIVER_PROVINCE'] ?? null);
            $codeAddress = implode(', ', array_filter([$wardName, $districtName, $provinceName]));

            if ($address && $codeAddress) {
                return $address . "\nTheo mã VTP: " . $codeAddress;
            }

            return $address ?: ($codeAddress ?: 'N/A');
        }

        if ($partnerCode === 'EMS') {
            $buyerInfo = $payload['BuyerInfo'] ?? $payload['ReceiverInfo'] ?? [];
            if (is_array($buyerInfo)) {
                $address = trim((string)($buyerInfo['Street'] ?? ''));
                $codeAddress = implode(', ', array_filter([
                    !empty($buyerInfo['WardID']) ? 'WardID: ' . $buyerInfo['WardID'] : null,
                    !empty($buyerInfo['DistrictID']) ? 'DistrictID: ' . $buyerInfo['DistrictID'] : null,
                    !empty($buyerInfo['ProvinceID']) ? 'ProvinceID: ' . $buyerInfo['ProvinceID'] : null,
                ]));

                if ($address && $codeAddress) {
                    return $address . "\nTheo mã EMS: " . $codeAddress;
                }

                return $address ?: ($codeAddress ?: 'N/A');
            }
        }

        return 'N/A';
    }

    private function getPartnerReceiverAddressLabel($log)
    {
        $partnerCode = strtoupper((string)$log->partner_code);
        if ($partnerCode === 'VIETTEL_POST') {
            return 'Địa chỉ VTP đã gửi';
        }

        if ($partnerCode === 'EMS') {
            return 'Địa chỉ EMS';
        }

        return 'Địa chỉ đối tác';
    }

    private function getAddressCompareStatus($log, array $payload, $appAddress, $partnerAddress)
    {
        if ($appAddress === 'N/A' || $partnerAddress === 'N/A') {
            return ['label' => 'Thiếu dữ liệu', 'class' => 'badge-secondary'];
        }

        $appParts = $this->getAppReceiverAddressParts($log->order);
        $partnerParts = $this->getPartnerReceiverAddressParts($log, $payload);
        $partnerCode = strtoupper((string)$log->partner_code);

        if (empty($appParts['ward'])) {
            return ['label' => 'Thiếu xã/phường nhập', 'class' => 'badge-warning'];
        }

        if ($partnerCode === 'VIETTEL_POST' && empty($payload['RECEIVER_WARD'])) {
            return ['label' => 'Thiếu mã xã/phường VTP', 'class' => 'badge-warning'];
        }

        if ($appParts['province'] && $partnerParts['province'] && !$this->sameAddressPart($appParts['province'], $partnerParts['province'])) {
            return ['label' => 'Khác tỉnh/thành', 'class' => 'badge-danger'];
        }

        if ($appParts['district'] && $partnerParts['district'] && !$this->sameAddressPart($appParts['district'], $partnerParts['district'])) {
            return ['label' => 'Khác huyện/quận', 'class' => 'badge-danger'];
        }

        if ($appParts['ward'] && $partnerParts['ward'] && !$this->sameAddressPart($appParts['ward'], $partnerParts['ward'])) {
            return ['label' => 'Khác xã/phường', 'class' => 'badge-danger'];
        }

        if ($appParts['street'] && $partnerParts['street'] && !$this->sameStreetAddress($appParts['street'], $partnerParts['street'])) {
            return ['label' => 'Khác địa chỉ nhập', 'class' => 'badge-danger'];
        }

        if (!$partnerParts['street'] && !$partnerParts['ward'] && !$partnerParts['district'] && !$partnerParts['province']) {
            return ['label' => 'Thiếu dữ liệu', 'class' => 'badge-secondary'];
        }

        return ['label' => 'Khớp', 'class' => 'badge-success'];
    }

    private function getAppReceiverAddressParts($order)
    {
        $receiver = $order ? $order->receiver : null;
        if (!$receiver) {
            return ['street' => null, 'ward' => null, 'district' => null, 'province' => null];
        }

        $isNewAddress = $receiver->address_scheme === 'new';

        return [
            'street' => $receiver->address ?? null,
            'ward' => $isNewAddress ? optional($receiver->newWard)->name : optional($receiver->ward)->ward_name,
            'district' => $isNewAddress ? null : optional($receiver->district)->district_name,
            'province' => $isNewAddress ? optional($receiver->newProvince)->name : optional($receiver->city)->city_name,
        ];
    }

    private function getPartnerReceiverAddressParts($log, array $payload)
    {
        $partnerCode = strtoupper((string)$log->partner_code);

        if ($partnerCode === 'VIETTEL_POST') {
            return [
                'street' => $payload['RECEIVER_ADDRESS'] ?? null,
                'ward' => $this->findWardNameByCode($payload['RECEIVER_WARD'] ?? null, false),
                'district' => $this->findDistrictNameByCode($payload['RECEIVER_DISTRICT'] ?? null, false),
                'province' => $this->findCityNameByCode($payload['RECEIVER_PROVINCE'] ?? null, false),
            ];
        }

        if ($partnerCode === 'EMS') {
            $buyerInfo = $payload['BuyerInfo'] ?? $payload['ReceiverInfo'] ?? [];
            return [
                'street' => is_array($buyerInfo) ? ($buyerInfo['Street'] ?? null) : null,
                'ward' => null,
                'district' => null,
                'province' => null,
            ];
        }

        return ['street' => null, 'ward' => null, 'district' => null, 'province' => null];
    }

    private function sameAddressPart($appValue, $partnerValue)
    {
        return $this->normalizeAddress($appValue) === $this->normalizeAddress($partnerValue);
    }

    private function sameStreetAddress($appValue, $partnerValue)
    {
        $appValue = $this->normalizeAddress($appValue);
        $partnerValue = $this->normalizeAddress($partnerValue);

        if (!$appValue || !$partnerValue) {
            return false;
        }

        return strpos($partnerValue, $appValue) !== false || strpos($appValue, $partnerValue) !== false;
    }

    private function normalizeAddress($address)
    {
        $address = mb_strtolower((string)$address, 'UTF-8');
        $address = str_replace(["\r", "\n"], ' ', $address);
        $address = preg_replace('/\b(theo ma|theo mã)\s+(vtp|ems)\s*:/iu', ' ', $address);
        $address = preg_replace('/\b(wardid|districtid|provinceid)\s*:\s*\d+/iu', ' ', $address);
        $address = preg_replace('/[^\pL\pN]+/u', ' ', $address);
        $address = preg_replace('/\s+/u', ' ', $address);

        return trim($address);
    }

    private function findWardNameByCode($code, $fallbackToCode = true)
    {
        if ($code === null || $code === '') {
            return null;
        }

        return Ward::where('ward_code', $code)->value('ward_name') ?: ($fallbackToCode ? 'Ward code: ' . $code : null);
    }

    private function findDistrictNameByCode($code, $fallbackToCode = true)
    {
        if ($code === null || $code === '') {
            return null;
        }

        return District::where('district_code', $code)->value('district_name') ?: ($fallbackToCode ? 'District code: ' . $code : null);
    }

    private function findCityNameByCode($code, $fallbackToCode = true)
    {
        if ($code === null || $code === '') {
            return null;
        }

        return City::where('city_code', $code)->value('city_name') ?: ($fallbackToCode ? 'Province code: ' . $code : null);
    }

    private function parseFilterDate($date)
    {
        $date = trim((string)$date);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return Carbon::create((int)$matches[1], (int)$matches[2], (int)$matches[3]);
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            return Carbon::create((int)$matches[3], (int)$matches[2], (int)$matches[1]);
        }

        return Carbon::parse($date);
    }

    public function checkApiStatus(Request $request, $provider, ApiStatusService $apiStatusService)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403);
        }

        try {
            $status = $apiStatusService->check($provider);
            if ($status['online']) {
                Flash::success($status['name'] . ' dang online. Cap nhat luc ' . Carbon::parse($status['checked_at'])->format('H:i d/m/Y') . '.');
            } else {
                Flash::error($status['name'] . ' dang offline: ' . $status['message']);
            }
        } catch (\Exception $e) {
            Flash::error('Kiem tra API that bai: ' . $e->getMessage());
        }

        return redirect()->route('order_partner_logs.index', $request->query());
    }

    public function runMickeyDetect(Request $request)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403);
        }

        $limit = (int)$request->input('limit', config('tracking.mickey_manual_limit', 20));
        $this->runMickeyCommandWithFlash(
            'mickey:detect-orders',
            $limit,
            'Quét đơn Mickey thành công',
            'Quét đơn Mickey thất bại'
        );

        return redirect()->route('order_partner_logs.index', $request->query());
    }

    public function runMickeySync(Request $request)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403);
        }

        $limit = (int)$request->input('limit', config('tracking.mickey_manual_limit', 20));
        try {
            $detect = $this->runArtisanCommand('mickey:detect-orders', $limit);
            if ((int)$detect['exit_code'] !== 0) {
                Flash::error('Đồng bộ trạng thái Mickey thất bại ở bước quét đơn. Mã lỗi: ' . $detect['exit_code'] . '. ' . $detect['summary']);
                return redirect()->route('order_partner_logs.index', $request->query());
            }

            $sync = $this->runArtisanCommand('mickey:sync-tracking', $limit);
            if ((int)$sync['exit_code'] !== 0) {
                Flash::error('Đồng bộ trạng thái Mickey thất bại ở bước cập nhật trạng thái. Mã lỗi: ' . $sync['exit_code'] . '. ' . $sync['summary']);
                return redirect()->route('order_partner_logs.index', $request->query());
            }

            Flash::success('Đồng bộ trạng thái Mickey thành công. Limit: ' . $limit . '. Detect: ' . $detect['summary'] . '. Sync: ' . $sync['summary']);
        } catch (\Exception $e) {
            Flash::error('Đồng bộ trạng thái Mickey thất bại: ' . $e->getMessage());
        }

        return redirect()->route('order_partner_logs.index', $request->query());
    }

    private function runMickeyCommandWithFlash($command, $limit, $successMessage, $errorMessage)
    {
        try {
            $result = $this->runArtisanCommand($command, $limit);
            $exitCode = $result['exit_code'];
            $summary = $result['summary'];
            $message = $successMessage . '. Limit: ' . $limit . ($summary ? '. ' . $summary : '.');

            if ((int)$exitCode === 0) {
                Flash::success($message);
                return;
            }

            Flash::error($errorMessage . '. Ma loi: ' . $exitCode . ($summary ? '. ' . $summary : '.'));
        } catch (\Exception $e) {
            Flash::error($errorMessage . ': ' . $e->getMessage());
        }
    }

    private function runArtisanCommand($command, $limit)
    {
        $exitCode = Artisan::call($command, ['--limit' => $limit]);
        return [
            'exit_code' => $exitCode,
            'summary' => $this->getArtisanSummary(),
        ];
    }

    private function getArtisanSummary()
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", Artisan::output()))));
        return $lines ? end($lines) : '';
    }

    public function cancel(Request $request, OrderPartnerLog $orderPartnerLog)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403);
        }

        $orderPartnerLog->load('order');

        if (!$this->canCancel($orderPartnerLog)) {
            Flash::error('Không thể huỷ đơn này. Đơn có thể đã bị huỷ, chưa có mã đối tác hoặc log không khớp với đối tác hiện tại.');
            return redirect()->route('order_partner_logs.index', $request->query());
        }

        $reason = $request->input('reason', 'Huy don tu BillHT');

        try {
            $cancelResult = $this->cancelPartnerOrder($orderPartnerLog, $reason);
            if (!$cancelResult['success']) {
                Flash::error('Huỷ đơn đối tác thất bại: ' . $cancelResult['message']);
                return redirect()->route('order_partner_logs.index', $request->query());
            }

            Flash::success('Huỷ đơn đối tác thành công. Mã đối tác đã được xoá để có thể đẩy đơn sang đối tác khác.');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Flash::error('Huỷ đơn đối tác thất bại: ' . $e->getMessage());
        }

        return redirect()->route('order_partner_logs.index', $request->query());
    }

    public function bulkCancel(Request $request)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403);
        }

        $logIds = array_filter((array)$request->input('log_ids', []));
        if (empty($logIds)) {
            Flash::error('Bạn vui lòng chọn log cần huỷ.');
            return redirect()->route('order_partner_logs.index', $request->query());
        }

        $reason = $request->input('reason', 'Huy don hang loat tu BillHT');
        $logs = OrderPartnerLog::with('order')->whereIn('id', $logIds)->get();
        $successCount = 0;
        $failedMessages = [];
        $processedOrderIds = [];

        foreach ($logs as $log) {
            if ($log->order_id && in_array($log->order_id, $processedOrderIds)) {
                $failedMessages[] = 'Log #' . $log->id . ': đơn này đã được xử lý trong lần huỷ hàng loạt hiện tại';
                continue;
            }

            if (!$this->canCancel($log)) {
                $failedMessages[] = 'Log #' . $log->id . ': không đủ điều kiện huỷ';
                continue;
            }

            try {
                $cancelResult = $this->cancelPartnerOrder($log, $reason);
                if ($cancelResult['success']) {
                    $successCount++;
                    $processedOrderIds[] = $log->order_id;
                } else {
                    $failedMessages[] = 'Log #' . $log->id . ': ' . $cancelResult['message'];
                }
            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                $failedMessages[] = 'Log #' . $log->id . ': ' . $e->getMessage();
            }
        }

        if ($successCount > 0) {
            Flash::success('Huỷ thành công ' . $successCount . ' đơn đối tác. Mã đối tác đã được xoá để có thể đẩy sang đối tác khác.');
        }

        if (!empty($failedMessages)) {
            Flash::error('Một số đơn huỷ thất bại: ' . implode('; ', array_slice($failedMessages, 0, 5)) . (count($failedMessages) > 5 ? '; ...' : ''));
        }

        return redirect()->route('order_partner_logs.index', $request->query());
    }

    public function bulkResolveAddresses(Request $request)
    {
        return $this->bulkSyncAction($request, 'resolve');
    }

    public function bulkPushViettel(Request $request)
    {
        return $this->bulkSyncAction($request, 'viettel');
    }

    public function bulkPushEms(Request $request)
    {
        return $this->bulkSyncAction($request, 'ems');
    }

    private function bulkSyncAction(Request $request, $action)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403);
        }

        $logIds = array_filter((array)$request->input('log_ids', []));
        if (empty($logIds)) {
            Flash::error('Bạn vui lòng chọn log cần thao tác.');
            return redirect()->route('order_partner_logs.index', $request->query());
        }

        $orders = $this->getOrdersFromLogIds($logIds);
        if ($orders->isEmpty()) {
            Flash::error('Không tìm thấy vận đơn hợp lệ từ các log đã chọn.');
            return redirect()->route('order_partner_logs.index', $request->query());
        }

        $resolvedOrders = 0;
        $resolvedAddresses = 0;
        $queued = 0;
        $orderController = app(OrderController::class);

        foreach ($orders as $order) {
            $result = $orderController->resolveLegacyAddressIdsForOrder($order);
            if ($result['updated_addresses'] > 0) {
                $resolvedOrders++;
                $resolvedAddresses += $result['updated_addresses'];
            }

            if ($action === 'viettel') {
                dispatch(new SendOrderViettelPostJob($order));
                $queued++;
            } elseif ($action === 'ems') {
                dispatch(new SendOrderEmsJob($order));
                $queued++;
            }
        }

        if ($action === 'resolve') {
            Flash::success("Đã tự gán lại địa chỉ cho {$resolvedOrders} vận đơn, {$resolvedAddresses} địa chỉ người gửi/người nhận.");
        } elseif ($action === 'viettel') {
            Flash::success("Đã đưa {$queued} vận đơn vào hàng chờ đẩy lại API Viettel. Đã tự gán lại địa chỉ cho {$resolvedOrders} vận đơn trước khi đẩy.");
        } else {
            Flash::success("Đã đưa {$queued} vận đơn vào hàng chờ đẩy lại API EMS. Đã tự gán lại địa chỉ cho {$resolvedOrders} vận đơn trước khi đẩy.");
        }

        return redirect()->route('order_partner_logs.index', $request->query());
    }

    private function getOrdersFromLogIds(array $logIds)
    {
        $orderIds = OrderPartnerLog::whereIn('id', $logIds)
            ->whereNotNull('order_id')
            ->pluck('order_id')
            ->filter()
            ->unique()
            ->values();

        return Order::with(['sender', 'receiver'])
            ->whereIn('id', $orderIds)
            ->get();
    }

    /**
     * Parses the raw JSON payload and response into formatted strings for the view
     */
    private function parseLogData($log, $isCancelledSync = false)
    {
        $payload = json_decode($log->payload, true) ?? [];
        
        // Cấu trúc EMS có chứa chuỗi JSON lồng trong trường 'Data'
        if (isset($payload['Data']) && is_string($payload['Data'])) {
            $innerData = json_decode($payload['Data'], true) ?? [];
            if (is_array($innerData)) {
                $payload = array_merge($payload, $innerData);
            }
        }
        
        // 1. Extract Order Number
        $orderNumber = $payload['ORDER_NUMBER'] ?? 
                      ($payload['order_code'] ?? 
                      ($payload['OrderCode'] ?? 
                      ($payload['ShippingCode'] ?? 'N/A')));
                      
        // 2. Extract Sender Name
        $senderName = $payload['SENDER_FULLNAME'] ?? 
                     ($payload['from_name'] ?? 
                     ($payload['SenderInfo']['SenderName'] ?? 
                     ($payload['SenderInfo']['FullName'] ?? 'N/A')));

        // 3. Extract Partner Name
        $partnerText = strtoupper($log->partner_code);
        if ($partnerText == 'VIETTEL_POST') $partnerText = 'VTP';
        if (empty($partnerText)) $partnerText = 'UNK';

        $appReceiverAddress = $this->formatAppReceiverAddress($log->order);
        $partnerReceiverAddress = $this->formatPartnerReceiverAddress($log, $payload);
        $addressCompareStatus = $this->getAddressCompareStatus($log, $payload, $appReceiverAddress, $partnerReceiverAddress);

        $isCancelLog = $this->isCancelPayload($payload);

        // 4. Parse Status Response Text and Errors
        $responseText = '';
        if (($isCancelLog && (int)$log->status === OrderPartnerLog::STATUS_SUCCESS) || ($isCancelledSync && (int)$log->status === OrderPartnerLog::STATUS_SUCCESS)) {
            $responseText = "<span class='badge badge-warning'>Đã huỷ đồng bộ - $partnerText</span>";
        } elseif ($log->status == 1 || strpos($log->response, "ORDER_NUMBER") !== false || strpos($log->response, "success") !== false) {
            $responseText = "<span class='badge badge-success'>Thành công - $partnerText</span>";
        } else {
            $errors = '';
            $resData = json_decode($log->response, true);

            // Match common raw string errors
            if (strpos($log->response, "[SENDER_ADDRESS]") !== false) $errors = "Sai hoặc thiếu địa chỉ người gửi";
            elseif (strpos($log->response, "[RECEIVER_PHONE]") !== false) $errors = "Sai/thiếu SĐT người nhận";
            elseif (strpos($log->response, "[SENDER_PHONE]") !== false) $errors = "Sai/thiếu SĐT người gửi";
            elseif (strpos($log->response, "[RECEIVER_ADDRESS]") !== false) $errors = "Sai/thiếu địa chỉ người nhận";
            elseif (strpos($log->response, "ORDER_SERVICE") !== false) $errors = "Thiếu trọng lượng";
            elseif (strpos($log->response, "Price does not apply") !== false) $errors = "Dịch vụ gửi không phù hợp nội tỉnh";
            elseif (strpos($log->response, '"026"') !== false) $errors = "Tỉnh/Thành phố người nhận không hợp lệ";
            elseif (strpos($log->response, '"011"') !== false) $errors = "SĐT hoặc địa chỉ gửi không được trống";
            elseif (strpos($log->response, "401 Unauthorized") !== false) $errors = "Lỗi xác thực Token, hãng từ chối kết nối (401)";
            
            // Try extracting detailed errors from JSON arrays (especially EMS data struct)
            if (empty($errors)) {
                if (strtoupper($log->partner_code) === 'EMS') {
                    if (isset($resData['data']) && is_array($resData['data'])) {
                        $errItems = [];
                        foreach ($resData['data'] as $item) {
                            if (isset($item['Parameter']) && isset($item['Message'])) {
                                $errItems[] = $item['Parameter'] . ': ' . $item['Message'];
                            }
                        }
                        $errors = implode('; ', $errItems);
                    }
                }
                if (empty($errors) && isset($resData['message'])) {
                    $errors = $resData['message'];
                }
            }

            // Fallback to raw string truncated
            if (!empty($errors)) {
                $responseText = "<span class='text-danger'><b>[$partnerText] $errors</b></span>";
            } else {
                $truncatedName = strlen($log->response) > 85 ? substr($log->response, 0, 85) . "..." : $log->response;
                $responseText = "<span class='text-danger'><b>Lỗi $partnerText:</b> " . htmlspecialchars($truncatedName) . "</span>";
            }
        }

        return (object)[
            'order_number' => $orderNumber,
            'sender_name' => $senderName,
            'response_html' => $responseText,
            'app_receiver_address' => $appReceiverAddress,
            'partner_receiver_address' => $partnerReceiverAddress,
            'partner_address_label' => $this->getPartnerReceiverAddressLabel($log),
            'address_compare_label' => $addressCompareStatus['label'],
            'address_compare_class' => $addressCompareStatus['class'],
        ];
    }

    private function getCancelledAtByOrderId($orderIds)
    {
        if (empty($orderIds)) {
            return [];
        }

        return OrderPartnerLog::whereIn('order_id', $orderIds)
            ->where('status', OrderPartnerLog::STATUS_SUCCESS)
            ->get(['order_id', 'payload', 'updated_at'])
            ->filter(function ($log) {
                $payload = json_decode($log->payload, true) ?? [];
                if (isset($payload['Data']) && is_string($payload['Data'])) {
                    $innerData = json_decode($payload['Data'], true) ?? [];
                    if (is_array($innerData)) {
                        $payload = array_merge($payload, $innerData);
                    }
                }

                return $this->isCancelPayload($payload);
            })
            ->groupBy('order_id')
            ->map(function ($logs) {
                return $logs->max('updated_at');
            })
            ->all();
    }

    private function isCancelPayload($payload)
    {
        $code = strtoupper((string)($payload['Code'] ?? $payload['code'] ?? ''));
        if ($code === 'PARTNER_ORDER_CANCEL') {
            return true;
        }

        return isset($payload['TYPE']) && (int)$payload['TYPE'] === 4;
    }

    private function canCancel($log)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            return false;
        }

        if ((int)$log->status !== OrderPartnerLog::STATUS_SUCCESS || !$log->order) {
            return false;
        }

        if (!$log->order->order_partner_code || !$log->order->partner_code) {
            return false;
        }

        return $this->normalizeLogPartnerCode($log->partner_code) === $log->order->partner_code;
    }

    private function cancelPartnerOrder($log, $reason)
    {
        $order = $log->order;
        $oldPartnerCode = $order->partner_code;
        $oldOrderPartnerCode = $order->order_partner_code;
        $orderOld = clone $order;

        if ($order->partner_code === Order::CODE_EMS) {
            $result = app(EmsService::class)->cancelOrder($order, $reason);
            $success = isset($result['code']) && $result['code'] === EmsService::STATUS_SUCCESS;
        } elseif ($order->partner_code === Order::CODE_VIETTEL_POST) {
            $viettelPostService = app(ViettelPostService::class);
            $result = $viettelPostService->cancelOrder($order, $reason);
            $success = $viettelPostService->isCancelSuccessful($result);
        } else {
            return [
                'success' => false,
                'message' => 'Đối tác của đơn không được hỗ trợ huỷ.',
            ];
        }

        if (!$success) {
            return [
                'success' => false,
                'message' => $this->extractErrorMessage($result),
            ];
        }

        DB::beginTransaction();

        $order->order_partner_code = null;
        $order->partner_code = null;
        $order->push_error = null;
        $order->save();

        app(OrderHistoryService::class)->createOrderHistory(
            $orderOld,
            $order,
            null,
            OrderHistory::NOT_TOTAL_ORDER,
            OrderHistory::TYPE_ORDER_UPDATE,
            'CANCEL_PARTNER',
            [
                'action_desc' => 'Huỷ đơn đối tác',
                'partner_code' => $oldPartnerCode,
                'order_partner_code' => $oldOrderPartnerCode,
                'reason' => $reason,
            ],
            $oldOrderPartnerCode,
            Order::MAP_MESSAGE_NOTI_PARTNER[$oldPartnerCode] ?? $oldPartnerCode
        );

        DB::commit();

        return [
            'success' => true,
            'message' => 'Huỷ thành công',
        ];
    }

    private function normalizeLogPartnerCode($partnerCode)
    {
        $partnerCode = strtoupper((string)$partnerCode);

        if ($partnerCode === PartnerConfig::CODE_EMS) {
            return Order::CODE_EMS;
        }

        if ($partnerCode === PartnerConfig::CODE_VIETTEL_POST || $partnerCode === 'VIETTEL_POST') {
            return Order::CODE_VIETTEL_POST;
        }

        return $partnerCode;
    }

    private function extractErrorMessage($result)
    {
        if (!is_array($result)) {
            return 'Không rõ lỗi';
        }

        if (!empty($result['message'])) {
            return $result['message'];
        }

        if (!empty($result['Message'])) {
            return $result['Message'];
        }

        if (!empty($result['data']) && is_string($result['data'])) {
            return $result['data'];
        }

        return 'Đối tác không trả về thông báo lỗi chi tiết.';
    }
}
