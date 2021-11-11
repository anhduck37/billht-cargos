<?php

namespace App\Http\Requests;

class OrderApiRequest extends BaseFormRequest
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
        return [
            'customer_name' => 'required',
            'tracking_code' => 'required|min:4',
            'products' => 'required|array',
            'crm_code' => 'required',
            'products' => 'required|array',
            'products.*.product_code' => 'required',
            'products.*.unit_price' => 'required|numeric',
            'products.*.quantity' => 'required|numeric',
        ];
    }
}
