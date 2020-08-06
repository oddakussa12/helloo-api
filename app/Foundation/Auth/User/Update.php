<?php
namespace App\Foundation\Auth\User;

use App\Jobs\Sms;
use App\Rules\UserPhone;
use App\Mail\UpdateEmail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use App\Custom\Uuid\RandomStringGenerator;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

trait Update
{
    public function fillAuth($request)
    {
        $user_phone = strval($request->input('user_phone' , ''));
        $user_phone_country = strval($request->input('user_phone_country' , "86"));
        $user_email = strval($request->input('user_email' , ''));
        $validationField = array(
            'email'=>$user_email
        );
        !empty($user_phone)&&$validationField['phone'] = $user_phone_country.$user_phone;
        $rule = [
            'phone' => [
                'bail',
                'required_without:email',
                new UserPhone(),
                function ($attribute, $value, $fail) use ($user_phone_country , $user_phone){
                    $phone = \DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
                    if(!blank($phone))
                    {
                        $fail(trans('validation.unique'));
                    }
                }
            ],
            'email'=> [
                'bail',
                'required_without:phone',
                'email',
                function ($attribute, $value, $fail) use ($user_phone_country , $user_phone){
                    $email = \DB::table('users')->where('user_email', $value)->first();
                    if(!blank($email))
                    {
                        $fail(trans('validation.unique'));
                    }
                }
            ]
        ];
        \Validator::make($validationField, $rule)->validate();
        $auth = auth()->user();
        if(!empty($user_email)&&$auth->user_email!=$user_email)
        {
            $auth->user_email = $user_email;
            $auth->save();
            $this->updateUser($auth , array(
                'user_email'=>$user_email
            ));
        }elseif (!empty($user_phone)&&!empty($user_phone_country))
        {
            $userPhone = \DB::table('users_phones')->where('user_id', $auth->user_id)->first();
            if(empty($userPhone))
            {
                \DB::table('users_phones')->insert(
                    array(
                        'user_id'=>$auth->user_id,
                        'user_phone_country'=>$user_phone_country,
                        'user_phone'=>$user_phone
                    )
                );
                $this->updateUser($auth , array(
                    'user_phone_country'=>$user_phone_country,
                    'user_phone'=>$user_phone,
                ));
            }
        }
    }
    public function verifyPassword(UserContract $user, $password)
    {
        $credentials['password'] = $password;
        return auth()->getProvider()->validateCredentials($user , $credentials);
    }

    public function sendPhoneCode($request)
    {
        $userId = auth()->id();
        $key = 'user.'.$userId.'.change.phone.code';
        $user_phone = strval($request->input('user_phone'));
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $validationField = array(
            'phone'=>$user_phone_country.$user_phone,
        );
        $rule = [
            'phone' => [
                'bail',
                'required',
                new UserPhone(),
                function ($attribute, $value, $fail) use ($key){
//                    if(Redis::exists($key))
//                    {
//                        $fail(trans('auth.throttle_limit'));
//                    }
                },
                function ($attribute, $value, $fail) use ($user_phone_country , $user_phone){
                    $phone = \DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
                    if(!blank($phone))
                    {
                        $fail(trans('validation.unique'));
                    }
                }
            ],
        ];
        \Validator::make($validationField, $rule)->validate();
        $code = (new RandomStringGenerator('1234567890'))->generate(6);
        Redis::set($key, $code);
        Redis::expire($key,config('common.phone_code_wait_time'));
        Sms::dispatch($user_phone , $code , $user_phone_country , 'update_phone')->onQueue('user_update_phone');
    }

    public function updateName($request)
    {
        $auth = auth()->user();
        $user_name = strval($request->input('name'));
        $password = strval($request->input('password' , ""));
        $validationField = array(
            'name'=>$user_name,
            'password'=>$password
        );
        $rule = [
            'name' => [
                'bail',
                'string',
                'min:4',
                'max:32',
                'regex:/^[0-9a-zA-Z]+$/u',
                function ($attribute, $value, $fail) use ($auth){
                    if(!$this->isCanUpdateName($auth->user_id))
                    {
                        $fail('Too close to the last username change date');
                    }
                },
                function ($attribute, $value, $fail){
                    $user = \DB::table('users')->where('user_name', $value)->first();
                    if(!blank($user))
                    {
                        $fail(trans('validation.unique'));
                    }
                }
            ],
            'password'=>[
                'bail',
                'required',
                'string',
                'min:6',
                'max:16',
                function ($attribute, $value, $fail) use ($auth){
                    if (!$this->verifyPassword($auth ,$value)) {
                        $fail(trans('auth.password_error'));
                    }
                }
            ],
        ];
        \Validator::make($validationField, $rule)->validate();
        $auth->user_name = $user_name;
        $auth->save();
        $this->updateUser($auth , array(
            'user_name'=>$user_name,
            'user_name_updated_at'=>time()
        ));
    }

    public function updatePhone($request)
    {
        $auth = auth()->user();
        $userId = $auth->user_id;
        $key = 'user.'.$userId.'.change.phone.code';
        $user_phone = strval($request->input('user_phone'));
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $password = strval($request->input('password' , ""));
        $code = strval($request->input('code' , ""));
        $validationField = array(
            'phone'=>$user_phone_country.$user_phone,
            'password'=>$password,
            'code'=>$code
        );
        $rule = [
            'phone'=>[
                'bail',
                'required',
                new UserPhone(),
                function ($attribute, $value, $fail) use ($user_phone_country , $user_phone){
                    $phone = \DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
                    if(!blank($phone))
                    {
                        $fail(trans('validation.custom.phone.unique'));
                    }
                }
            ],
            'password'=>[
                'bail',
                'required',
                'string',
                'min:6',
                'max:16',
                function ($attribute, $value, $fail) use ($auth){
                    if (!$this->verifyPassword($auth ,$value)) {
                        $fail(trans('auth.password_error'));
                    }
                }
            ],
            'code'=>[
                'bail',
                'required',
                'string',
                'size:6',
                function ($attribute, $value, $fail) use ($key){
                    if(!Redis::exists($key)||$value!=Redis::get($key))
                    {
                        $fail('Verification code error');
                    }
                },
            ]
        ];
        \Validator::make($validationField, $rule)->validate();
        $userPhone = \DB::table('users_phones')->where('user_id', $userId)->first();
        if(!empty($userPhone))
        {
            $data = array(
                'user_phone'=>$user_phone,
                'user_phone_country'=>$user_phone_country,
            );
            \DB::table('users_phones')->where('user_id', $userId)->update($data);
        }else{
            $data = array(
                'user_phone'=>$user_phone,
                'user_phone_country'=>$user_phone_country,
            );
            \DB::table('users_phones')->insert(array_merge(array('user_id'=>$userId) , $data));
        }
        $this->updateUser($auth , $data);
        Redis::del($key);
    }

    public function sendEmailCode($request)
    {
        $userId = auth()->id();
        $key = 'user.'.$userId.'.change.email.code';
        $user_email = strval($request->input('user_email'));
        $validationField = array(
            'email'=>$user_email,
        );
        $rule = [
            'email' => [
                'bail',
                'required',
                'email',
                function ($attribute, $value, $fail) use ($key){
//                    if(Redis::exists($key))
//                    {
////                        $fail(trans('auth.throttle_limit'));
//                    }
                },
                function ($attribute, $value, $fail){
                    $user = \DB::table('users')->where('user_email', $value)->first();
                    if(!blank($user))
                    {
                        $fail(trans('validation.unique'));
                    }
                }
            ]
        ];
        \Validator::make($validationField, $rule)->validate();
        $code = (new RandomStringGenerator('1234567890'))->generate(6);
        Redis::set($key, $code);
        Redis::expire($key,config('common.email_code_wait_time'));
        Mail::to($user_email)->queue((new UpdateEmail($code))
            ->onQueue('user_update_email'));
    }

    public function updateEmail($request)
    {
        $auth = auth()->user();
        $userId = $auth->user_id;
        $key = 'user.'.$userId.'.change.email.code';
        $user_email = strval($request->input('user_email'));
        $password = strval($request->input('password' , ""));
        $code = strval($request->input('code'));
        $validationField = array(
            'email'=>$user_email,
            'password'=>$password,
            'code'=>$code,
        );
        $rule = [
            'email' => [
                'bail',
                'required',
                'email',
                function ($attribute, $value, $fail){
                    $user = \DB::table('users')->where('user_email', $value)->first();
                    if(!blank($user))
                    {
                        $fail(trans('validation.unique'));
                    }
                }
            ],
            'password'=>[
                'bail',
                'required',
                'string',
                'min:6',
                'max:16',
                function ($attribute, $value, $fail) use ($auth){
                    if (!$this->verifyPassword($auth ,$value)) {
                        $fail(trans('auth.password_error'));
                    }
                }
            ],
            'code'=>[
                'bail',
                'required',
                'string',
                'size:6',
                function ($attribute, $value, $fail) use ($key){
                    if(!Redis::exists($key)||$value!=Redis::get($key))
                    {
                        $fail('Verification code error');
                    }
                },
            ]
        ];
        \Validator::make($validationField, $rule)->validate();
        $data = array(
            'user_email'=>$user_email
        );
        $auth->fill($data);
        $auth->save();
        $this->updateUser($auth , $data);
        Redis::del($key);
    }

    public function updateMessages()
    {
        return [
            'name.unique'=>'This account already exists'
        ];
    }

    public function updateRules()
    {
        return array(
            'country_code'=>[
                'bail',
                'filled',
                'string',
                function ($attribute, $value, $fail) {
                    $value = strtoupper($value);
                    if (!in_array($value , config('countries'))) {
                        $fail(trans('validation.regex'));
                    }
                },
            ],
            'user_gender'=>[
                'bail',
                'filled',
                Rule::in([1, 0 , '0' , '1']),
            ],
            'user_nick_name'=>[
                'bail',
                'filled',
                'string',
                'min:4',
                'max:13'
            ],
            'user_birthday'=>[
                'bail',
                'filled',
                'date',
                'after:'.date('Y-m-d' , strtotime('-101years')),
                'before:'.date('Y-m-d' , strtotime('-1years')),
            ],
            'user_about'=>[
                'bail',
                'filled',
                'string',
            ],
            'user_avatar'=>[
                'bail',
                'filled',
                'string'
            ],
            'user_cover'=>[
                'bail',
                'filled',
                'string'
            ],
            'user_picture'=>[
                'bail',
                'filled',
                'array'
            ]
        );
    }
}