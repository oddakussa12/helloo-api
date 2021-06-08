<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryOrderRequest extends FormRequest
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
            'user_id' => [
                'bail',
                'required'
            ],
            'user_contact' => [
                'bail',
                'required',
                'string'
            ],
            'user_name' => [
                'bail',
                'filled',
                'string'
            ],
            'user_address' => [
                'bail',
                'filled',
                'string'
            ]
        ];
    }

}
