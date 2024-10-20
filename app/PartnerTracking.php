<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerTracking extends Model
{
    public $fillable = [
        'order_id',
        'order_partner_code',
        'order_reference',
        'order_statusdate',
        'order_status',
        'status_name',
        'note',
        'money_conllection',
        'money_feecod',
        'money_total',
        'expected_delivery',
        'product_weight',
        'order_service',
        'location_currently',
        'money_totalfee',
        'order_payment',
        'expected_delivery_date',
        'detail',
        'voucher_value',
        'money_collection_origin',
        'employee_name',
        'employee_phone',
        'is_returning',
        'pod',
        'receiver_fullname',
    ];
}
