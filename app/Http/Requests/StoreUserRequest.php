<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            //UTF-8正则匹配汉字、字母、数字、横杠、下划线、空格，如下：(不要空格可以去掉\s)
            'name'=>'required|regex:/^[\x{4e00}-\x{9fa5}0-9a-zA-Z-_]+$/u|min:4|max:32|unique:users,user_name',
            'email'=>'required|email|unique:users,user_email',
            'password'=>'required|min:4|max:16',
        ];
    }

//    public function messages(){
//        return [
//            'name.required' => 'A title is required',
//            'email.required'  => 'email is required',
//            'password.required'  => 'password is required',
//            'password.min'  => 'A message is required1',
//            'password.max'  => 'A message is required2',
//        ];
//    }
//
//    public function attributes()
//    {
//        return [
//            'name'=>trans('name'),
//            'password'=>'kata sandi',
//            'email'=>'email',
//        ];
//    }
}
