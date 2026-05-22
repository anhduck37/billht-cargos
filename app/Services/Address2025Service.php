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
        
        // Remove prefixes
        $prefixes = [
            'thành phố ', 'tp ', 'tp. ', 'tp.', 'tỉnh ',
            'quận ', 'huyện ', 'thị xã ', 'tx ', 'tx. ', 'tx.',
            'phường ', 'xã ', 'thị trấn ', 'tt ', 'tt. ', 'tt.'
        ];
        
        foreach ($prefixes as $prefix) {
            if (mb_strpos($name, $prefix, 0, 'UTF-8') === 0) {
                $name = mb_substr($name, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8');
                $name = trim($name);
            }
        }
        
        // Handle common abbreviations
        $abbreviations = [
            'sg' => 'hồ chí minh',
            'hcm' => 'hồ chí minh',
            'hcmc' => 'hồ chí minh',
            'hn' => 'hà nội',
            'hp' => 'hải phòng',
            'đn' => 'đà nẵng',
            'dn' => 'đà nẵng',
            'vt' => 'bà rịa - vũng tàu',
            'brvt' => 'bà rịa - vũng tàu',
            'bd' => 'bình dương',
            'dnai' => 'đồng nai',
            'tn' => 'tây ninh',
            'la' => 'long an',
            'tg' => 'tiền giang',
            'ct' => 'cần thơ',
            'ag' => 'an giang',
            'kg' => 'kiên giang',
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
