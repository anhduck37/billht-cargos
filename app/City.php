<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'city_name',
        'city_code',
        'language',
    ];
    protected $table = 'citys';

    const MAP_CITY_VIETTEL_POST = [
        [
            'city' => [
                'AN GIANG',
                'VŨNG TÀU',
                'BẠC LIÊU',
                'BẾN TRE',
                'BÌNH DƯƠNG',
                'BÌNH PHƯỚC',
                'BÌNH THUẬN',
                'BÌNH ĐỊNH',
                'CÀ MAU',
                'CẦN THƠ',
                'ĐẮK LẮK',
                'ĐỒNG NAI',
                'ĐỒNG THÁP',
                'GIA LAI',
                'HẬU GIANG',
                'HCM',
                'KHÁNH HOÀ',
                'KIÊN GIANG',
                'LÂM ĐỒNG',
                'LONG AN',
                'NINH THUẬN',
                'PHÚ YÊN',
                'SÓC TRĂNG',
                'TÂY NINH',
                'TIỀN GIANG',
                'TRÀ VINH',
                'VĨNH LONG',
            ],
            'group_id' => "A51A Bạch Đằng, phường 2, Tân Bình, HCM"
        ],
        [
            'city' => [
                'BẮC GIANG',
                'BẮC KẠN',
                'BẮC NINH',
                'CAO BẰNG',
                'ĐÀ NẴNG',
                'ĐIỆN BIÊN',
                'HÀ NAM',
                'HÀ NỘI',
                'HÀ TĨNH',
                'HẢI DƯƠNG',
                'HẢI PHÒNG',
                'HOÀ BÌNH',
                'HƯNG YÊN',
                'LAI CHÂU',
                'LẠNG SƠN',
                'LÀO CAI',
                'NAM ĐỊNH',
                'NGHỆ AN',
                'NINH BÌNH',
                'PHÚ THỌ',
                'QUẢNG BÌNH',
                'QUẢNG NAM',
                'QUẢNG NGÃI',
                'QUẢNG NINH',
                'QUẢNG TRỊ',
                'SƠN LA',
                'THÁI BÌNH',
                'THÁI NGUYÊN',
                'THANH HOÁ',
                'HUẾ',
                'TUYÊN QUANG',
                'VĨNH PHÚC',
                'YÊN BÁI',
                'HÀ GIANG'
            ],
            'group_id' => "27/71 Hoàng Văn Thái, Khương Trung, Thanh Xuân, Hà Nội"
        ],
    ];
}
