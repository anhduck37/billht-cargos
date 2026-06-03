<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if (auth()->user()->level != User::LEVEL_POSTMAN) {
                return;
            }

            if (!empty($this->input('order_id'))) {
                return;
            }

            $invoiceCode = strtoupper(trim((string) $this->input('order.invoice_code')));
            if (strpos($invoiceCode, 'HE') !== 0) {
                return;
            }

            if (!Order::where('order_code', $invoiceCode)->exists()) {
                $validator->errors()->add('order.invoice_code', 'Mã vận đơn HE phải tồn tại trên hệ thống.');
            }
        });
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

        // Các trường có dấu (*) trên form tạo/sửa đơn phải được chặn ở backend.
        // Bưu tá có luồng tạo/cập nhật đơn riêng nên giữ rule riêng bên dưới.
        if (auth()->user()->level != User::LEVEL_POSTMAN) {
            $rule = [
                'sender.sender_name' => 'required',
                'sender.sender_phone' => 'required',
                'sender.address' => 'required',
                'receiver.receiver_name' => 'required',
                'receiver.receiver_phone' => 'required',
                'receiver.address' => 'required',
            ];

            if (($formData['sender']['address_scheme'] ?? null) === 'new') {
                $rule['sender.new_province_id'] = 'required';
                $rule['sender.new_ward_id'] = 'required';
            } else {
                $rule['sender.city_id'] = 'required';
            }

            if (($formData['receiver']['address_scheme'] ?? null) === 'new') {
                $rule['receiver.new_province_id'] = 'required';
                $rule['receiver.new_ward_id'] = 'required';
            } else {
                $rule['receiver.city_id'] = 'required';
                $rule['receiver.district_id'] = 'required';
                $rule['receiver.ward_id'] = 'required';
            }
        }

        if (!empty($formData['order_id'])) {
            $order = Order::find($formData['order_id']);
            if (auth()->user()->level == User::LEVEL_POSTMAN && !isset($order->image)) {
                $rule['image_data'] = 'required';
            }
            if ($order && isset($formData['order']) && isset($formData['order']['invoice_code']) && $order->order_code != $formData['order']['invoice_code']) {
                $rule['order.invoice_code'] = 'unique:orders,order_code';
            }
        } else {
            if (auth()->user()->level == User::LEVEL_POSTMAN) {
                $rule['image_data'] = 'required';
            }
        }

        if (auth()->user()->level == User::LEVEL_POSTMAN) {
            if (isset($rule['order.invoice_code'])) {
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
            'sender.sender_name.required' => 'Tên cá nhân/Công ty người gửi là bắt buộc.',
            'sender.sender_phone.required' => 'Số điện thoại người gửi là bắt buộc.',
            'sender.address.required' => 'Số nhà, tên đường người gửi là bắt buộc.',
            'receiver.receiver_name.required' => 'Tên cá nhân/Công ty người nhận là bắt buộc.',
            'receiver.receiver_phone.required' => 'Số điện thoại người nhận là bắt buộc.',
            'receiver.address.required' => 'Số nhà, tên đường người nhận là bắt buộc.',
            'receiver.city_id.required' => 'Tỉnh/Thành phố người nhận là bắt buộc.',
            'receiver.district_id.required' => 'Huyện/Quận người nhận là bắt buộc.',
            'receiver.ward_id.required' => 'Xã/Phường người nhận là bắt buộc.',
            'sender.city_id.required' => 'Tỉnh/Thành phố người gửi là bắt buộc.',
            'sender.district_id.required' => 'Huyện/Quận người gửi là bắt buộc.',
            'sender.ward_id.required' => 'Xã/Phường người gửi là bắt buộc.',
            'sender.new_province_id.required' => 'Tỉnh/Thành phố mới của người gửi là bắt buộc.',
            'sender.new_ward_id.required' => 'Xã/Phường mới của người gửi là bắt buộc.',
            'receiver.new_province_id.required' => 'Tỉnh/Thành phố mới của người nhận là bắt buộc.',
            'receiver.new_ward_id.required' => 'Xã/Phường mới của người nhận là bắt buộc.',
            'order.weight.required' => 'Cân nặng là bắt buộc.',
        ];
    }
}
