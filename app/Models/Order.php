<?php

namespace App\Models;

use App\OrderImage;
use App\OrderTracking;
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
        'invoice_code',
        'person_charge',
        'signator'
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

	const DELIVERY_STATUS_PERSON_CHARGE = 4;

    const DELIVERY_STATUS_RECEIVED = 5;

    const DELIVERY_MAP = [
        self::DELIVERY_STATUS_PROCESSING => 'Chấp nhận gửi',
		self::DELIVERY_STATUS_OK => 'Giao thành công',
		self::DELIVERY_STATUS_RETURN => 'Đi khỏi bưu cục',
		self::DELIVERY_STATUS_PERSON_CHARGE => 'Đã giao bưu tá đi phát',
        self::DELIVERY_STATUS_RECEIVED => 'Đã đến bưu cục'
    ];

    const PAYMENT_METHOD_COD = 1;
    const PAYMENT_METHOD_INTERNET_BANKING = 2;
    const PAYMENT_METHOD_OTHER = 3;
    const PAYMENT_METHOD_LAST = 4;
    const PAYMENT_METHOD_MAP = [
      self::PAYMENT_METHOD_COD => 'COD',
        self::PAYMENT_METHOD_LAST => 'Thanh toán cuối tháng',
      self::PAYMENT_METHOD_INTERNET_BANKING => 'Người nhận trả cước',
      self::PAYMENT_METHOD_OTHER => 'Đã thanh toán',

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

    public function getPersonCharge() {
        return $this->hasOne(User::class, 'id', 'person_charge');
    }

    public function converDate($date) {
        return (string) app(OrderService::class)->implodeDate($date);
    }

    public function services() {
        return $this->hasMany(Service::class, 'order_id', 'id');
    }

    public function serviceArray($order_id) {
        return $this->join('services', 'services.order_id', '=','orders.id')->where('services.order_id', $order_id)->select('services.service')->get()->pluck('service')->toArray();
    }

    public function image() {
        return $this->hasOne(OrderImage::class, 'order_id', 'id');
    }

    public function order_trackings() {
        return $this->hasMany(OrderTracking::class, 'order_id', 'id');
    }
}
