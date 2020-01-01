<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
//            'post_title' => 'bail|required|string|between:1,200',
//            'post_title' => 'bail|present|between:0,3000',
            'tag_slug' => 'bail|present|array',
//            'post_content' => 'bail|present|between:0,3000',
            'post_content' => 'bail|required|string|between:1,3000',
//            'post_event_country' => 'bail|required|string|between:1,10',
        ];
    }
}
