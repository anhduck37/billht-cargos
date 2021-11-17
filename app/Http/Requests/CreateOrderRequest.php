<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;

class CreateOrderRequest extends FormRequest
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
//        dd($this->request->all());
        $rules = [
            'sender.sender_name' => 'required',
//            'sender.sender_phone' => 'required',
//            'sender.address' => 'required',
            'receiver.receiver_name' => 'required',
//            'receiver.receiver_phone' => 'required',
            'receiver.address' => 'required'
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'sender.sender_name.required' => 'Tên cá nhân / Công ty người gửi không được bỏ trống.',
            'receiver.receiver_name.required' => 'Tên cá nhân / Công ty người nhận không được bỏ trống.',
            'receiver.address.required' => 'Địa chỉ người nhận không được bỏ trống.'
        ];
    }
}
