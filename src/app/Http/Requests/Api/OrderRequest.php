<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
            'auth_code' => ['required'],
            'amount' => ['required', 'numeric', 'min:0', 'not_in:0', 'max:100000000'],
        ];
    }

    public function messages()
    {
        return [
            'auth_code.required' => '条码号错误',
            'amount.required' => '金额必填',
            'amount.numeric' => '金额类型不对',
            'amount.min' => '金额必须大于0',
            'amount.not_in' => '金额必须大于0',
            'amount.max' => '金额超过在线支付允许上限',
        ];
    }
}
