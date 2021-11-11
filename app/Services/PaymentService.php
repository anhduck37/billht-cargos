<?php

namespace App\Services;

use App\Models\PercentCommission;
use App\User;

class PaymentService
{
    // const PERCENT_COMMISSION_LEVEL_1 = 20;
    // const PERCENT_COMMISSION_LEVEL_2 = 23;
    // const PERCENT_COMMISSION_LEVEL_3 = 26;
    // const PERCENT_COMMISSION_LEVEL_4 = 30;
    public function dataCommision($lang)
    {
//        $data = [
//            'vi' => [
//                ['min' => 0, 'max' => 40, 'data' => 20],
//                ['min' => 40, 'max' => 70, 'data' => 23],
//                ['min' => 70, 'max' => 200, 'data' => 26],
//                ['min' => 200, 'max' => -1, 'data' => 30],
//            ],
//            'id' => [
//                ['min' => 0, 'max' => 40, 'data' => 20],
//                ['min' => 40, 'max' => 70, 'data' => 23],
//                ['min' => 70, 'max' => 200, 'data' => 26],
//                ['min' => 200, 'max' => -1, 'data' => 30],
//            ],
//            'th' => [
//                ['min' => 0, 'max' => 40, 'data' => 20],
//                ['min' => 40, 'max' => 70, 'data' => 23],
//                ['min' => 70, 'max' => 200, 'data' => 26],
//                ['min' => 200, 'max' => -1, 'data' => 30],
//            ]
//        ];
//        return $data[$lang];
        $percentCommission = PercentCommission::where('lang', $lang)->first();
        $data = ($percentCommission) ? $percentCommission->data: '[{"max": "0", "min": "0", "data": "0"}]';
        return json_decode($data);
    }

    public function calPercentCommission($valueCompare, $lang)
    {
//        $dataCommision = $this->dataCommision($lang);
//        $percent = 0;
//        if (!empty($dataCommision)) {
//            foreach ($dataCommision as $commision) {
//                if ($commision['max'] < 0 && $orderCount > $commision['min']) {
//                    $percent = $commision['data'];
//                } else if ($commision['max'] > 0 && $orderCount > $commision['min'] && $orderCount <= $commision['max'] ) {
//                    $percent = $commision['data'];
//                }
//            }
//        }
//        return $percent;
        $dataCommission = $this->dataCommision($lang);
        $percent = 0;
        if (!empty($dataCommission)) {
            foreach ($dataCommission as $k => $commission) {
                if ($commission->max < 0 && $valueCompare > $commission->min) {
                    $percent = $commission->data;
                } else if (($commission->max > 0 && ($valueCompare > $commission->min || ($k == 0 && $valueCompare >= $commission->min) ) && $valueCompare <= $commission->max) || ($valueCompare > $commission->max && $commission->max > 0) ) {
                    $percent = $commission->data;
                }
            }
        }
        return $percent;

    }

    public function calTotalMoney($orderCount, $totalMoney, $lang)
    {
        $percent = $this->calPercentCommission( $lang === User::LANG_BEEGREEN ? $totalMoney: $orderCount, $lang);
        return [$percent * $totalMoney / 100, $percent];
    }

    public function getMonths() {
        $months = [];
        for ($i = 1; $i <= 12; $i++){
            $months[$i] = $i;
        }
        return $months;
    }

    public function getYears($minYear) {
        $years = [];
        for ($i = date("Y"); $i >= $minYear; $i--) {
            $years[$i] = $i;
        }
        return $years;
    }


}
