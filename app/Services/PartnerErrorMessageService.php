<?php

namespace App\Services;

use App\Models\Order;

class PartnerErrorMessageService
{
    public function normalizeResult($partnerCode, array $result, array $payload = [])
    {
        if ($partnerCode === Order::CODE_VIETTEL_POST) {
            return $this->normalizeViettelResult($result, $payload);
        }

        if ($partnerCode === Order::CODE_EMS) {
            return $this->normalizeEmsResult($result);
        }

        return $result;
    }

    public function normalizeText($partnerCode, $message)
    {
        $message = trim((string)$message);
        if ($message === '') {
            return $message;
        }

        if ($partnerCode === Order::CODE_VIETTEL_POST) {
            return $this->translateViettelMessage($message);
        }

        if ($partnerCode === Order::CODE_EMS) {
            return $this->translateEmsMessage($message);
        }

        return $message;
    }

    private function normalizeViettelResult(array $result, array $payload)
    {
        $message = $result['message'] ?? $result['Message'] ?? '';
        $translated = $this->translateViettelMessage($message);

        if ($translated !== $message && $translated !== '') {
            $result['_raw_message'] = $message;
            $result['message'] = $translated;
        }

        return $result;
    }

    private function translateViettelMessage($message)
    {
        $message = trim((string)$message);
        if ($message === '') {
            return $message;
        }

        $upperMessage = strtoupper($message);
        if (strpos($upperMessage, 'INCORRECT DATA') === false) {
            return $message;
        }

        $fieldLabels = [
            'SENDER_PROVINCE' => 'mã tỉnh/thành phố người gửi',
            'SENDER_DISTRICT' => 'mã huyện/quận người gửi',
            'SENDER_WARD' => 'mã xã/phường người gửi',
            'RECEIVER_PROVINCE' => 'mã tỉnh/thành phố người nhận',
            'RECEIVER_DISTRICT' => 'mã huyện/quận người nhận',
            'RECEIVER_WARD' => 'mã xã/phường người nhận',
        ];

        $found = [];
        foreach ($fieldLabels as $field => $label) {
            if (strpos($upperMessage, $field) !== false) {
                $found[] = $label;
            }
        }

        if (empty($found)) {
            return 'Viettel báo dữ liệu địa chỉ không hợp lệ. Vui lòng kiểm tra lại tỉnh/huyện/xã và mapping VTP trước khi đẩy lại.';
        }

        $details = implode(', ', array_unique($found));
        $hint = 'Vui lòng kiểm tra lại địa chỉ trên app và mapping VTP của xã/phường liên quan.';

        if (strpos($upperMessage, 'WITH ADDRESS NEW') !== false) {
            $hint = 'Đơn đang dùng địa chỉ mới. Vui lòng kiểm tra mapping VTP phải đủ mã tỉnh, mã huyện, mã xã cho người gửi/người nhận.';
        } elseif (strpos($upperMessage, 'WITH ADDRESS OLD') !== false) {
            $hint = 'Viettel đang hiểu phần địa chỉ này theo dữ liệu cũ. Vui lòng kiểm tra tỉnh/huyện/xã cũ hoặc chuyển đúng sang địa chỉ mới có mapping VTP.';
        }

        return 'Viettel báo ' . $details . ' không hợp lệ. ' . $hint;
    }

    private function normalizeEmsResult(array $result)
    {
        if (!empty($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $index => $item) {
                if (is_array($item) && isset($item['Parameter'], $item['Message'])) {
                    $result['data'][$index]['Message'] = $this->translateEmsFieldMessage($item['Parameter'], $item['Message']);
                    $result['data'][$index]['RawMessage'] = $item['Message'];
                }
            }
        }

        if (!empty($result['message'])) {
            $result['_raw_message'] = $result['message'];
            $result['message'] = $this->translateEmsMessage($result['message']);
        }

        return $result;
    }

    private function translateEmsFieldMessage($parameter, $message)
    {
        $parameter = (string)$parameter;
        $message = trim((string)$message);

        $fieldLabels = [
            'BuyerInfo.ProvinceID' => 'tỉnh/thành phố người nhận',
            'BuyerInfo.DistrictID' => 'huyện/quận người nhận',
            'BuyerInfo.WardID' => 'xã/phường người nhận',
            'ReceiverInfo.ProvinceID' => 'tỉnh/thành phố người nhận',
            'ReceiverInfo.DistrictID' => 'huyện/quận người nhận',
            'ReceiverInfo.WardID' => 'xã/phường người nhận',
            'SenderInfo.ProvinceID' => 'tỉnh/thành phố người gửi',
            'SenderInfo.DistrictID' => 'huyện/quận người gửi',
            'SenderInfo.WardID' => 'xã/phường người gửi',
            'TransportInfo.TransportFee' => 'thông tin tính cước',
        ];

        foreach ($fieldLabels as $field => $label) {
            if ($parameter === $field) {
                if ($field === 'TransportInfo.TransportFee') {
                    return 'EMS chưa tính được cước. Vui lòng kiểm tra dịch vụ, trọng lượng, kích thước và địa chỉ gửi/nhận.';
                }

                return 'EMS báo ' . $label . ' không hợp lệ hoặc còn thiếu. Vui lòng kiểm tra lại mapping EMS và địa chỉ trên app.';
            }
        }

        return $message ?: 'EMS báo dữ liệu không hợp lệ.';
    }

    private function translateEmsMessage($message)
    {
        $message = trim((string)$message);
        if ($message === '') {
            return $message;
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);

        if (strpos($lower, 'lỗi hệ thống') !== false || strpos($lower, 'loi he thong') !== false) {
            return 'EMS đang trả lỗi hệ thống. Vui lòng thử đẩy lại sau; nếu lỗi lặp lại, gửi mã vận đơn và thời gian lỗi cho EMS kiểm tra.';
        }

        if (strpos($lower, 'timeout') !== false || strpos($lower, 'timed out') !== false || strpos($lower, 'cURL error 28') !== false) {
            return 'Kết nối tới EMS quá thời gian chờ. Vui lòng thử đẩy lại sau vài phút.';
        }

        return $message;
    }
}
