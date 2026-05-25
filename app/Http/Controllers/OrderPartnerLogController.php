<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\OrderPartnerLog;
use App\PartnerConfig;
use App\OrderHistory;
use App\Services\EmsService;
use App\Services\OrderHistoryService;
use App\Services\ViettelPostService;
use App\User;
use Carbon\Carbon;
use Flash;
use Illuminate\Support\Facades\DB;

class OrderPartnerLogController extends Controller
{
    public function index(Request $request)
    {
        // Query builder for filtering
        $query = OrderPartnerLog::with('order');

        // Filter by Status
        if ($request->has('filter_status') && $request->filter_status !== '') {
            $query->where('status', $request->filter_status);
        }

        // Filter by Date
        if ($request->has('filter_date') && $request->filter_date !== '') {
            $query->whereDate('updated_at', $request->filter_date);
        }

        // Filter by Partner
        if ($request->has('filter_partner') && $request->filter_partner !== '') {
            $query->where('partner_code', strtoupper($request->filter_partner));
        }

        // Paginate results
        $logs = $query->orderBy('updated_at', 'desc')->paginate(50);
        
        // Count statistics for the current month
        $currentMonth = Carbon::now()->format('Y-m');
        $successQuery = OrderPartnerLog::where('status', 1)
                                      ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$currentMonth]);
                                      
        $errorQuery = OrderPartnerLog::where('status', 0)
                                    ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$currentMonth]);

        if ($request->has('filter_partner') && $request->filter_partner !== '') {
            $successQuery->where('partner_code', strtoupper($request->filter_partner));
            $errorQuery->where('partner_code', strtoupper($request->filter_partner));
        }

        $successCount = $successQuery->count();
        $errorCount = $errorQuery->count();

        $orderIds = $logs->getCollection()->pluck('order_id')->filter()->unique()->values()->all();
        $cancelledOrderIds = $this->getCancelledOrderIds($orderIds);

        // Process the payloads before sending to the view
        foreach ($logs as $log) {
            $log->parsed = $this->parseLogData($log, in_array($log->order_id, $cancelledOrderIds));
            $log->can_cancel = $this->canCancel($log);
        }

        return view('order_partner_logs.index', compact('logs', 'successCount', 'errorCount'));
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

        $isCancelLog = $this->isCancelPayload($payload);

        // 4. Parse Status Response Text and Errors
        $responseText = '';
        if (($isCancelLog && (int)$log->status === OrderPartnerLog::STATUS_SUCCESS) || $isCancelledSync) {
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
            elseif (strpos($log->response, "026") !== false) $errors = "Tỉnh/Thành phố người nhận không hợp lệ";
            elseif (strpos($log->response, "011") !== false) $errors = "SĐT hoặc địa chỉ gửi không được trống";
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
        ];
    }

    private function getCancelledOrderIds($orderIds)
    {
        if (empty($orderIds)) {
            return [];
        }

        return OrderPartnerLog::whereIn('order_id', $orderIds)
            ->where('status', OrderPartnerLog::STATUS_SUCCESS)
            ->get(['order_id', 'payload'])
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
            ->pluck('order_id')
            ->unique()
            ->values()
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
