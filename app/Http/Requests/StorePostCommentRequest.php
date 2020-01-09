<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return config('common.authorization.create_post_comment');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'post_uuid' => 'bail|required|string|alpha_dash|size:36',
            'comment_comment_p_id' => 'bail|integer|numeric',
            'comment_content' => 'bail|present|nullable|string|between:0,800',
        ];
    }
}
