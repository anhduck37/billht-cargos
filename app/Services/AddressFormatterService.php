<?php

namespace App\Services;

class AddressFormatterService
{
    public function getFullAddress($addressModel)
    {
        if (!$addressModel) {
            return '';
        }

        $parts = [];
        if (!empty($addressModel->address)) {
            $parts[] = $addressModel->address;
        }

        if (($addressModel->address_scheme ?? 'old') === 'new') {
            if (!empty($addressModel->newWard)) {
                $parts[] = $addressModel->newWard->name;
            }
            if (!empty($addressModel->newProvince)) {
                $parts[] = $addressModel->newProvince->name;
            }
        } else {
            if (!empty($addressModel->ward)) {
                $parts[] = $addressModel->ward->ward_name;
            }
            if (!empty($addressModel->district)) {
                $parts[] = $addressModel->district->district_name;
            }
            if (!empty($addressModel->city)) {
                $parts[] = $addressModel->city->city_name;
            }
        }

        return implode(', ', array_filter($parts));
    }
}
