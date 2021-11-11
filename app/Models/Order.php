<?php

namespace App\Models;

use App\Partner;
use App\Receiver;
use App\Sender;
use App\Service;
use App\Services\OrderService;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

/**
 * Class Order
 * @package App\Models
 * @version March 10, 2021, 8:30 am UTC
 *
 */
class Order extends Model
{

    public $table = 'orders';


    //protected $dates = ['deleted_at'];



    public $fillable = [
        'sender_id',
        'receiver_id',
        'order_status',
        'delivery_status',
        'payment_method',
        'order_code',
        'total',
        'user_id',
        'order_date',
//        'delivery_date',
        'language',
        'partner',
        'weight',
        'height',
        'width',
        'note',
        'department',
        'invoice_code'
    ];

	const ORDER_OK = 1;
	const ORDER_RETURN = 2;
    const ORDER_CANCEL = 3;
    const ORDER_BLANK = 4;

    const MAP_ORDER_STATUS = [
		self::ORDER_BLANK => 'Blank',
		self::ORDER_OK => 'Success',
		self::ORDER_RETURN =>'Return',
        self::ORDER_CANCEL => 'Cancel'
    ];

    const DELIVERY_STATUS_OK = 1;

	const DELIVERY_STATUS_RETURN = 2;

	const DELIVERY_STATUS_PROCESSING = 3;

	const DELIVERY_STATUS_SUCCESSFULL = 4;

    const DELIVERY_MAP = [
		self::DELIVERY_STATUS_OK => 'Đang vận chuyển',
		self::DELIVERY_STATUS_RETURN => 'Đơn hoàn trả',
		self::DELIVERY_STATUS_PROCESSING => 'Đơn hủy',
		self::DELIVERY_STATUS_SUCCESSFULL => 'Đơn thành công',
    ];

    const PAYMENT_METHOD_COD = 1;
    const PAYMENT_METHOD_INTERNET_BANKING = 2;
    const PAYMENT_METHOD_OTHER = 3;
    const PAYMENT_METHOD_LAST = 4;
    const PAYMENT_METHOD_MAP = [
      self::PAYMENT_METHOD_COD => 'COD',
        self::PAYMENT_METHOD_LAST => 'Thanh toán cuối tháng',
      self::PAYMENT_METHOD_INTERNET_BANKING => 'Internet Banking',
      self::PAYMENT_METHOD_OTHER => 'Khác',

    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [

    ];

    public function getOrderStatusNameAttribute($attribute)
    {
        return (array_key_exists($this->order_status, self::MAP_ORDER_STATUS)) ? self::MAP_ORDER_STATUS[$this->order_status] : '';
    }

    public function getPaymentMethodNameAttribute()
    {
        return array_key_exists($this->payment_method, self::PAYMENT_METHOD_MAP) ? self::PAYMENT_METHOD_MAP[$this->payment_method] : '';
    }

    public function getOrderDeliveryNameAttribute($attribute)
    {
        return array_key_exists($this->delivery_status, self::DELIVERY_MAP) ? self::DELIVERY_MAP[$this->delivery_status] : '';
    }
    public function sender() {
        return $this->hasOne(Sender::class, 'id','sender_id');
    }
    public function receiver() {
        return $this->hasOne(Receiver::class, 'id', 'receiver_id');
    }
    public function getPartner() {
        return $this->hasOne(Partner::class, 'id', 'partner');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id','user_id');
    }

    public function converDate($date) {
        return app(OrderService::class)->implodeDate($date);
    }

    public function services() {
        return $this->hasMany(Service::class, 'order_id', 'id');
    }

    public function serviceArray($order_id) {
        return $this->join('services', 'services.order_id', '=','orders.id')->where('services.order_id', $order_id)->select('services.service')->get()->pluck('service')->toArray();
    }
}
