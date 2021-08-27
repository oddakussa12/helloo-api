<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecipientRequest extends FormRequest
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
            'name' => 'bail|required|string|max:128',
            'phone' => 'bail|required|string|max:32',
            'longitude' => [
                'bail',
                'required',
                'max:32',
                'regex:/^[\-\+]?(0(\.\d{1,10})?|([1-9](\d)?)(\.\d{1,10})?|1[0-7]\d{1}(\.\d{1,10})?|180\.0{1,10})$/'
            ],
            'latitude' => [
                'bail',
                'required',
                'max:32',
                'regex:/^[\-\+]?((0|([1-8]\d?))(\.\d{1,10})?|90(\.0{1,10})?)$/'
            ],
            'address' => 'bail|required|string|max:512',
            'specific' => 'bail|required|string|max:512',
        ];
    }
}
