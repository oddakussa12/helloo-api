<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
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
            'feedback_name' => 'bail|nullable|string|max:100',
            'feedback_email' => 'bail|nullable|string|email|max:100',
            'feedback_content' => 'bail|required|string|between:1,2000',
        ];
    }
}
