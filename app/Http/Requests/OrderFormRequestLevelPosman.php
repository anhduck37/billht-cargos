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
        if(!empty($formData['order_id'])) {
            $order = Order::find($formData['order_id']);
            if(auth()->user()->level == User::LEVEL_POSTMAN && !isset($order->image)) {
                $rule['image_data'] = 'required';
            }
            if($order && isset($formData['order']) && $order->order_code != $formData['order']['invoice_code']) {
                $rule['order.invoice_code'] = 'unique:orders,order_code';
            }
        } else {
            $rule['order.invoice_code'] = 'unique:orders,order_code';
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
            'order.invoice_code.unique' => 'Mã vận đơn đã tồn tại.'
        ];
    }
}
