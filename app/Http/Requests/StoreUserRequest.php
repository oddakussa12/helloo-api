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
            //
            'name'=>'required|unique:users,user_name',
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
