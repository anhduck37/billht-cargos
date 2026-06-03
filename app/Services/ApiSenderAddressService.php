<?php

namespace App\Services;

use App\Models\Order;
use App\Sender;
use Illuminate\Support\Facades\Log;

class ApiSenderAddressService
{
    const DEFAULT_SENDER_ADDRESS = 'Số 27 Ngõ 71 Hoàng Văn Thái, Xã Phương Liệt, Hà Nội';
    const DEFAULT_SENDER_STREET = 'Số 27 Ngõ 71 Hoàng Văn Thái';

    public function ensureDefaultSenderAddressForApi(Order $order, $partnerCode = null): array
    {
        $order->loadMissing('sender');
        $sender = $order->sender;

        if (!$sender || !$this->senderNeedsDefaultApiAddress($sender, $partnerCode)) {
            return ['updated' => false];
        }

        $parsed = app(Address2025Service::class)->parseFullAddress(self::DEFAULT_SENDER_ADDRESS);

        if (empty($parsed['success']) || empty($parsed['new_province_id']) || empty($parsed['new_ward_id'])) {
            Log::warning('Default sender API address cannot be parsed', [
                'order_id' => $order->id,
                'address' => self::DEFAULT_SENDER_ADDRESS,
                'errors' => $parsed['errors'] ?? [],
            ]);

            return ['updated' => false];
        }

        $sender->address = $parsed['address'] ?: self::DEFAULT_SENDER_STREET;
        $sender->address_scheme = 'new';
        $sender->new_province_id = $parsed['new_province_id'];
        $sender->new_ward_id = $parsed['new_ward_id'];
        $sender->city_id = null;
        $sender->district_id = null;
        $sender->ward_id = null;
        $sender->save();

        if ($order->address_scheme !== 'new') {
            $order->address_scheme = 'new';
            $order->save();
        }

        $order->unsetRelation('sender');
        $order->load('sender');

        return ['updated' => true];
    }

    private function senderNeedsDefaultApiAddress(Sender $sender, $partnerCode = null): bool
    {
        if (trim((string)$sender->address) === '') {
            return true;
        }

        $scheme = $sender->address_scheme ?: 'old';
        if ($scheme === 'new') {
            if (empty($sender->new_province_id) || empty($sender->new_ward_id)) {
                return true;
            }

            return $partnerCode ? !$this->senderHasPartnerMapping($sender, $partnerCode) : false;
        }

        return empty($sender->city_id) || empty($sender->district_id) || empty($sender->ward_id);
    }

    private function senderHasPartnerMapping(Sender $sender, $partnerCode): bool
    {
        if (empty($sender->new_ward_id)) {
            return false;
        }

        $partnerCode = strtoupper((string)$partnerCode);
        $mapping = app(Address2025Service::class)->getPartnerMapping($sender->new_ward_id, $partnerCode);

        if (!$mapping || empty($mapping->partner_province_code)) {
            return false;
        }

        if ($partnerCode === Order::CODE_VIETTEL_POST) {
            return !empty($mapping->partner_district_code) && !empty($mapping->partner_ward_code);
        }

        return true;
    }
}
