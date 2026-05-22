<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\OrderPartnerLog;
use Carbon\Carbon;

class OrderPartnerLogController extends Controller
{
    public function index(Request $request)
    {
        // Query builder for filtering
        $query = OrderPartnerLog::query();

        // Filter by Status
        if ($request->has('filter_status') && $request->filter_status !== null) {
            $query->where('status', $request->filter_status);
        }

        // Filter by Date
        if ($request->has('filter_date') && $request->filter_date !== null) {
            $query->whereDate('updated_at', $request->filter_date);
        }

        // Filter by Partner
        if ($request->has('filter_partner') && $request->filter_partner !== null) {
            $query->where('partner_code', $request->filter_partner);
        }

        // Paginate results
        $logs = $query->orderBy('updated_at', 'desc')->paginate(50);
        
        // Count statistics for the current month
        $currentMonth = Carbon::now()->format('Y-m');
        $successQuery = OrderPartnerLog::where('status', 1)
                                      ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$currentMonth]);
                                      
        $errorQuery = OrderPartnerLog::where('status', 0)
                                    ->whereRaw("DATE_FORMAT(updated_at, '%Y-%m') = ?", [$currentMonth]);

        if ($request->has('filter_partner') && $request->filter_partner !== null) {
            $successQuery->where('partner_code', $request->filter_partner);
            $errorQuery->where('partner_code', $request->filter_partner);
        }

        $successCount = $successQuery->count();
        $errorCount = $errorQuery->count();

        // Process the payloads before sending to the view
        foreach ($logs as $log) {
            $log->parsed = $this->parseLogData($log);
        }

        return view('order_partner_logs.index', compact('logs', 'successCount', 'errorCount'));
    }

    /**
     * Parses the raw JSON payload and response into formatted strings for the view
     */
    private function parseLogData($log)
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
                      ($payload['OrderCode'] ?? 'N/A'));
                      
        // 2. Extract Sender Name
        $senderName = $payload['SENDER_FULLNAME'] ?? 
                     ($payload['from_name'] ?? 
                     ($payload['SenderInfo']['SenderName'] ?? 
                     ($payload['SenderInfo']['FullName'] ?? 'N/A')));

        // 3. Extract Partner Name
        $partnerText = strtoupper($log->partner_code);
        if ($partnerText == 'VIETTEL_POST') $partnerText = 'VTP';
        if (empty($partnerText)) $partnerText = 'UNK';

        // 4. Parse Status Response Text and Errors
        $responseText = '';
        if ($log->status == 1 || strpos($log->response, "ORDER_NUMBER") !== false || strpos($log->response, "success") !== false) {
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
                if ($log->partner_code === 'ems') {
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
}
