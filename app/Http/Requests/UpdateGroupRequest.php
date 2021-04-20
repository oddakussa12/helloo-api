<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
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
            'name' => 'bail|required_without:avatar|string|max:128',
            'avatar' => 'bail|required_without:name|string|max:256',
        ];
    }

    public function messages()
    {
        return [
            'name.required_without' => 'Group name is required',
            'name.string' => 'Incorrect group name format',
            'name.max' => 'The group name cannot exceed 128 characters',
            'avatar.required_without' => 'Group avatar is required',
            'avatar.string' => 'Incorrect group avatar format',
            'avatar.max' => 'The group avatar cannot exceed 256 characters',
        ];
    }
}
