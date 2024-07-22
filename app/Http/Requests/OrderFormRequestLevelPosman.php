<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\User;
use Illuminate\Foundation\Http\FormRequest;

class OrderFormRequestLevelPosman extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
public function rules()
{
    $formData = request()->all();
    $rule = [];

    // Áp dụng các trường bắt buộc chỉ cho User::LEVEL_USER
    if (auth()->user()->level == User::LEVEL_USER) {
        $rule = [
            'sender.sender_name' => 'required',
            'sender.sender_phone' => 'required',
            'sender.address' => 'required',
            'receiver.receiver_name' => 'required',
            'receiver.receiver_phone' => 'required',
            'receiver.address' => 'required',
            'sender.city_id' => 'required',
            'sender.district_id' => 'required',
            'sender.ward_id' => 'required',
            'receiver.city_id' => 'required',
            'receiver.district_id' => 'required',
            'receiver.ward_id' => 'required',
            'order.note' => 'required',
            'order.type' => 'required',
            // 'order.weight' => 'required'
        ];
    }

    // Các quy tắc validation khác cho các level khác
    if(!empty($formData['order_id'])) {
        $order = Order::find($formData['order_id']);
        if(auth()->user()->level == User::LEVEL_POSTMAN && !isset($order->image)) {
            $rule['image_data'] = 'required';
        }
        if($order && isset($formData['order']) && isset($formData['order']['invoice_code']) && $order->order_code != $formData['order']['invoice_code']) {
            $rule['order.invoice_code'] = 'unique:orders,order_code';
        }
    } else {
        if(auth()->user()->level == User::LEVEL_POSTMAN) {
            $rule['image_data'] = 'required';
        }
    }

    if(auth()->user()->level == User::LEVEL_POSTMAN) {
        if(isset($rule['order.invoice_code'])) {
            $rule['order.invoice_code'] .= '|required';
        } else {
            $rule['order.invoice_code'] = 'required';
        }
        $rule['order.delivery_status'] = 'required';
        $rule['order.signator'] = 'required';
    }

    return $rule;
}

    public function messages()
    {
        return [
            'image_data.required' => 'Chụp ảnh là bắt buộc.',
            'order.invoice_code.required' => 'Nhập Mã vận đơn.',
            'order.delivery_status.required' => 'Tình trạng vận chuyển là bắt buộc.',
            'order.signator.required' => 'Người ký nhận là bắt buộc.',
            'order.invoice_code.unique' => 'Mã vận đơn đã tồn tại.',
            'sender.sender_name.required' => 'Tên cá nhân/ Công ty là bắt buộc',
            'sender.sender_phone.required' => 'Số điện thoại là bắt buộc',
            'sender.address.required' => 'Địa chỉ là bắt buộc',
            'receiver.receiver_name.required' => 'Tên cá nhân/ Công ty là bắt buộc',
            'receiver.receiver_phone.required' => 'Số điện thoại là bắt buộc',
            'receiver.address.required' => 'Địa chỉ là bắt buộc',
            'receiver.city_id.required' => 'Tỉnh / Thành phố  là bắt buộc',
            'receiver.district_id.required' => 'Huyện / Quận là bắt buộc',
            'receiver.ward_id.required' => 'Xã / Phường là bắt buộc',
            'sender.city_id.required' => 'Tỉnh / Thành phố  là bắt buộc',
            'sender.district_id.required' => 'Huyện / Quận là bắt buộc',
            'sender.ward_id.required' => 'Xã / Phường là bắt buộc',
            'order.note.required' => 'Nội dung là bắt buộc',
            'order.type.required' => 'Loại hàng hóa là bắt buộc',
            'order.weight.required' => 'Cân nặng là bắt buộc'
        ];
    }
}
