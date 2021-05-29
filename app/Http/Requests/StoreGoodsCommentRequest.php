<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoodsCommentRequest extends FormRequest
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
            'content' => [
                'bail',
                'present',
                'required_if:type,reply',
//                'string',
                'max:2000',
            ],
//            'media'=>[
//                'bail',
//                'present',
//                'array',
//                'max:3',
//            ],
            'point'=>[
                'bail',
                'required_if:type,comment',
                'numeric',
                'between:0,5'
            ],
            'service'=>[
                'bail',
                'required_if:type,comment',
                'numeric',
                'between:0,5'
            ],
            'quality'=>[
                'bail',
                'required_if:type,comment',
                'numeric',
                'between:0,5'
            ],
            'type'=>[
                'bail',
                'required',
                Rule::in('comment' , 'reply')
            ]
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
