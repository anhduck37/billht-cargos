<?php

namespace App\Http\Requests;

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
        $rule = [
            'order.invoice_code' => 'unique:orders,order_code'
        ];
        if(auth()->user()->level == User::LEVEL_POSTMAN) {
            $rule['image_data'] = 'required';
            $rule['order.invoice_code'] = 'required';
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
            'order.invoice_code.unique' => 'Mã vận đơn đã tồn tại.'
        ];
    }
}
