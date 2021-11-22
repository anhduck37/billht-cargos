<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'order_id',
        'service',
        'type'
    ];

    //dịch vụ trong nước
    const CPN = 1; //Chuyển phát nhanh
    const PTN = 2;//phát trong ngày
    const WEEKEND = 3; //cuối tuần
    const TIME_9 = 4;// 9:00 AM
    const TIME_12 = 5; //12:00 AM
    const HT = 6;//hỏa tốc
    const HST = 7; //Hồ sơ thầu
    const TK = 8;//Tiết kiệm
    const DB = 9; //Đường bố
    const DIFF = 10; //Khác
    //dịch vụ cộng thêm
    const BP = 11;//Báo phát
    const PTT = 12;//Phát tận tay
    const HDGTGT = 13;//Hóa đơn GTGT
    const PHG = 14;//Phát hẹn giờ
    const CDG = 15;//Có đóng gói
    const KDG = 16;//Không đóng gói
    const CBH = 17;//Có bảo hiểm
    const KBH = 18;//Không bảo hiểm
    //dịch vụ quốc tế
    const EXPRESS = 19;
    const ECONOMY = 20;
    const OTHERS = 21;

    const SERVICE_DOMESTIC = 1;
    const SERVICE_EXTRA = 2;
    const SERVICE_INTERNATIONAL = 3;

    const SERVICE_MAP = [
        self::SERVICE_DOMESTIC => [
            'name' => 'Dịch vụ trong nước',
            'value' => [
                self::CPN => 'Chuyển phát nhanh',
                self::PTN => 'Phát trong ngày',
                self::WEEKEND => 'T7 C.Nhật',
                self::TIME_9 => '9:00 AM',
                self::TIME_12 => '12:00 AM',
                self::HT => 'Hỏa tốc',
                self::HST => 'Hồ sơ thầu',
                self::TK => 'Tiết kiệm',
                self::DB => 'Đường bộ',
                self::DIFF => 'Khác',
            ]
        ],
        self::SERVICE_EXTRA => [
            'name' => 'Dịch vụ cộng thêm',
            'value' => [
                self::BP => 'Báo phát',
                self::PTT => 'Phát tận tay',
                self::HDGTGT => 'Hóa đơn GTGT',
                self::PHG => 'Phát hẹn giờ',
                self::CDG => 'Có đóng gói',
                self::KDG => 'Không đóng gói',
                self::CBH => 'Có bảo hiểm',
                self::KBH => 'Không bảo hiểm',
            ]
        ],
        self::SERVICE_INTERNATIONAL => [
            'name' => 'Dịch vụ quốc tế',
            'value' => [
                self::EXPRESS => 'Express',
                self::ECONOMY => 'Economy',
                self::OTHERS => 'Others',
            ]
        ],
    ];
}
