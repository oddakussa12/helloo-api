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
            'longitude' => 'bail|required|string|max:32',
            'latitude' => 'bail|required|string|max:32',
            'address' => 'bail|required|string|max:512',
            'specific' => 'bail|required|string|max:512',
        ];
    }
}
