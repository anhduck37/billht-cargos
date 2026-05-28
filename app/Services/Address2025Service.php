<?php

namespace App\Services;

use App\NewProvince;
use App\NewWard;
use App\NewAddressPartnerMapping;
use Illuminate\Support\Str;

class Address2025Service
{
    /**
     * Parse 1 dòng địa chỉ thành Tỉnh, Xã mới và địa chỉ chi tiết
     * VD: Nha Anh Duc, Xom Van Minh, Truong Luu, Ha Tinh
     * 
     * @param string $fullAddress
     * @return array
     */
    public function parseFullAddress($fullAddress)
    {
        $result = [
            'success' => false,
            'address' => $fullAddress,
            'new_province_id' => null,
            'new_ward_id' => null,
            'errors' => [],
        ];

        if (empty($fullAddress)) {
            $result['errors'][] = 'Địa chỉ trống';
            return $result;
        }

        $parts = array_map('trim', explode(',', $fullAddress));
        $count = count($parts);

        if ($count < 2) {
            $result['errors'][] = 'Địa chỉ phải có ít nhất 2 thành phần cách nhau bằng dấu phẩy (Xã/Phường, Tỉnh/TP)';
            return $result;
        }

        if ($count == 2) {
            $provinceName = $parts[1];
            $wardName = $parts[0];
            $detailAddress = $fullAddress; // Giữ nguyên full để không mất thông tin
        } else {
            $provinceName = $parts[$count - 1];
            $wardName = $parts[$count - 2];
            $detailAddress = implode(', ', array_slice($parts, 0, $count - 2));
        }

        if ($this->looksLikeOldDistrictName($wardName)) {
            $result['errors'][] = "Thành phần Xã/Phường mới đang là Quận/Huyện/TP cũ: {$wardName}";
            return $result;
        }

        $province = $this->findProvince($provinceName);
        if (!$province) {
            $result['errors'][] = "Không tìm thấy Tỉnh/Thành phố mới: {$provinceName}";
            return $result;
        }

        $ward = $this->findWard($wardName, $province->id);
        if (!$ward) {
            $result['errors'][] = "Không tìm thấy Phường/Xã mới: {$wardName} thuộc {$province->name}";
            return $result;
        }

        $result['success'] = true;
        $result['address'] = $detailAddress;
        $result['new_province_id'] = $province->id;
        $result['new_ward_id'] = $ward->id;

        return $result;
    }

    private function looksLikeOldDistrictName($name)
    {
        $name = mb_strtolower(trim((string)$name), 'UTF-8');
        $name = Str::ascii($name);
        $name = preg_replace('/[^a-z0-9\s.]/i', ' ', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));

        $districtPrefixes = [
            'quan ', 'q ', 'q. ',
            'huyen ', 'h ', 'h. ',
            'thi xa ', 'tx ', 'tx. ',
            'thanh pho ', 'tp ', 'tp. ',
        ];

        foreach ($districtPrefixes as $prefix) {
            if (strpos($name, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    public function findProvince($name)
    {
        $normalized = $this->normalizeName($name);
        return NewProvince::where('normalized_name', $normalized)->first();
    }

    public function findWard($name, $provinceId)
    {
        $normalized = $this->normalizeName($name);
        return NewWard::where('new_province_id', $provinceId)
            ->where('normalized_name', $normalized)
            ->first();
    }

    public function normalizeName($name)
    {
        $name = mb_strtolower(trim($name), 'UTF-8');
        $name = str_replace(['Ð', 'ð'], ['Đ', 'đ'], $name);
        $name = Str::ascii($name);
        
        // Remove prefixes
        $prefixes = [
            'thanh pho ', 'tp ', 'tp. ', 'tp.', 'tinh ',
            'quan ', 'huyen ', 'thi xa ', 'tx ', 'tx. ', 'tx.',
            'phuong ', 'xa ', 'thi tran ', 'tt ', 'tt. ', 'tt.'
        ];
        
        foreach ($prefixes as $prefix) {
            if (mb_strpos($name, $prefix, 0, 'UTF-8') === 0) {
                $name = mb_substr($name, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8');
                $name = trim($name);
            }
        }
        
        // Handle common abbreviations
        $abbreviations = [
            'sg' => 'ho chi minh',
            'hcm' => 'ho chi minh',
            'hcmc' => 'ho chi minh',
            'hn' => 'ha noi',
            'hp' => 'hai phong',
            'dn' => 'da nang',
            'vt' => 'ba ria vung tau',
            'brvt' => 'ba ria vung tau',
            'bd' => 'binh duong',
            'dnai' => 'dong nai',
            'tn' => 'tay ninh',
            'la' => 'long an',
            'tg' => 'tien giang',
            'ct' => 'can tho',
            'ag' => 'an giang',
            'kg' => 'kien giang',
            'cm' => 'cà mau',
        ];

        if (array_key_exists($name, $abbreviations)) {
            $name = $abbreviations[$name];
        }

        // Remove accents and special chars
        $name = Str::slug($name, ' ');
        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim($name);
    }

    public function getPartnerMapping($newWardId, $partnerCode)
    {
        return NewAddressPartnerMapping::where('new_ward_id', $newWardId)
            ->where('partner_code', strtoupper($partnerCode))
            ->where('mapping_status', 'mapped')
            ->first();
    }
}
