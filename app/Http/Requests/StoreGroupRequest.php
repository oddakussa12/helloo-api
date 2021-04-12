<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
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
            'name' => 'bail|required|string|max:200',
            'user_id' => 'bail|required|array|between:2,100',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Group name is required',
            'name.string' => 'Incorrect group name format',
            'name.max' => 'The group name cannot exceed 200 characters',
            'user_id.required' => 'Group members are required',
            'user_id.array' => 'Group member ID is illegal',
            'user_id.between' => 'The number of group members is limited to 2-100',
        ];
    }
}
