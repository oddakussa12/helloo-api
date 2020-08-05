<?php

namespace App\Http\Requests;

use App\Rules\UserPhone;
use Illuminate\Foundation\Http\FormRequest;

class ForgetPasswordRequest extends FormRequest
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
            'email'=>'bail|required_without:user_phone|email',
            'user_phone'=>'bail|required_without:email|string',
        ];
    }

}
