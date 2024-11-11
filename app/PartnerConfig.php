<?php

namespace App;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class PartnerConfig extends Model
{
    protected $fillable = [
        'partner_code',
        'token'
    ];

    const CODE_VIETTEL_POST = 'VIETTEL_POST';
    const CODE_EMS = 'EMS';
    const STATUS_VIETTEL_POST = [
        -100 => 'Đơn hàng mới được tạo, chưa được phê duyệt',
        -108 => 'Đơn hàng gửi tại bưu điện',
        -109 => 'Đơn hàng đã được gửi tại các điểm thu gom',
        -110 => 'Đơn hàng được chuyển qua bưu điện',
        100  => 'Nhận đơn hàng của khách hàng Viettel Post xử lý đơn hàng',
        101  => 'ViettelPost yêu cầu khách hàng hủy đơn hàng',
        102  => 'Lệnh xử lý',
        103  => 'Giao hàng tới Bưu điện Bưu điện Bưu điện xử lý đơn hàng',
        104  => 'Giao hàng cho người đưa thư Người đưa thư',
        105  => 'Người đưa thư nhận được đơn hàng',
        106  => 'Đối tác yêu cầu khôi phục đơn hàng',
        107  => 'Đối tác yêu cầu hủy đơn hàng qua API',
        200  => 'Nhận từ Bưu điện-Người nhận',
        201  => 'Hủy phím trong phiếu giao hàng',
        202  => 'Phiếu giao hàng đúng',
        300  => 'Đóng hồ sơ giao hàng',
        301  => 'Đóng gói giao hàng Giao hàng từ',
        302  => 'Đóng theo dõi thư gửi Gửi từ',
        303  => 'Đóng làn xe tải giao hàng Giao hàng từ',
        400  => 'Nhận hồ sơ thu nhập Nhận tại',
        401  => 'Nhận túi đựng Nhận tại',
        402  => 'Theo dõi thư nhận Nhận tại',
        403  => 'Nhận làn xe tải Nhận tại',
        500  => 'Giao hàng tận nơi Người đưa thư',
        501  => 'Thành Công-Mang lại thành công',
        502  => 'Phát lại Bưu điện Người nhận',
        503  => 'Hủy-Yêu cầu của khách hàng',
        504  => 'Thành công - Giao lại cho khách hàng',
        505  => 'Hàng tồn kho-Giao lại Bưu điện Người nhận',
        506  => 'Hàng tồn kho-Không đón khách',
        507  => 'Hàng tồn kho-Khách hàng nhận hàng tại Pos Office',
        508  => 'Giao hàng',
        509  => 'Phát đi Bưu điện khác',
        510  => 'Hủy giao hàng',
        515  => 'Chuyển phát Bưu điện phê duyệt lệnh hoàn trả',
        550  => 'Yêu cầu Giao hàng Bưu điện gửi lại'
    ];

    const MAP_STATUS_VIETTEL_POST = [
        105 => Order::DELIVERY_STATUS_RECEIVED,
        200 => Order::DELIVERY_STATUS_RETURN,
        202 => Order::DELIVERY_STATUS_RETURN,
        300 => Order::DELIVERY_STATUS_RETURN,
        320 => Order::DELIVERY_STATUS_RETURN,
        400 => Order::DELIVERY_STATUS_RETURN,
        500 => Order::DELIVERY_STATUS_PERSON_CHARGE,
        506 => Order::DELIVERY_STATUS_PERSON_CHARGE,
        570 => Order::DELIVERY_STATUS_PERSON_CHARGE,
        508 => Order::DELIVERY_STATUS_PERSON_CHARGE,
        509 => Order::DELIVERY_STATUS_PERSON_CHARGE,
        550 => Order::DELIVERY_STATUS_PERSON_CHARGE,
        501 => Order::DELIVERY_STATUS_OK
    ];
}
