<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginUserRequest extends FormRequest
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
//            //
            'name'=>'required',
            'password'=>'required|min:4|max:16',
        ];
    }
//
//    public function attributes()
//    {
//        return [
//            'name'=>'nama',
//            'password'=>'kata sandi',
//        ];
//    }

//    public function messages(){
//        return [
//            'name.required' => 'A title is required',
//            'email.required'  => 'email is required',
//            'password.required'  => 'password is required',
//            'password.min'  => 'A message is required1',
//            'password.max'  => 'A message is required2',
//        ];
//    }
}
