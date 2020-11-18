<?php
namespace App\Foundation\Auth\User;

use Carbon\Carbon;
use App\Models\User;
use App\Jobs\EasySms;
use App\Rules\UserPhone;
use App\Messages\SignInMessage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Custom\EasySms\PhoneNumber;
use App\Messages\UpdatePhoneMessage;
use Illuminate\Support\Facades\Redis;
use App\Messages\ForgetPasswordMessage;
use Illuminate\Support\Facades\Validator;
use App\Custom\Uuid\RandomStringGenerator;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

trait Update
{

    public function resetByPhone($request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $code = strval($request->input('code' , ''));
        $phone = $user_phone_country.$user_phone;
        $password = strval($request->input('password'));
        $rules = [
            'user_phone' => [
                'bail',
                'required',
                'string',
                new UserPhone()
            ],
            'password' => 'bail|required|string|min:6|max:16',
            'code' => [
                'bail',
                'required',
                'string',
                'size:4',
                function ($attribute, $value, $fail) use ($phone){
                    $key = 'helloo:account:service:account-reset-password-sms-code:'.$phone;
                    $code = Redis::get($key);
                    if($code===null||$code!=$value)
                    {
                        config('common.is_verification')&&$fail('sms error');
                    }else{
                        Redis::del($key);
                    }
                },
            ],
        ];
        $validationField = array(
            'code' => $code,
            'user_phone'=>$phone,
            'password'=>$password,
        );
        Validator::make($validationField, $rules)->validate();
        $user = DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
        if(blank($user))
        {
            abort(404 , 'Account does not exist!');
        }
        $res = DB::table('users')->where('user_id' , $user->user_id)->update(
            array('user_pwd'=>bcrypt($password))
        );
        if($res<=0)
        {
            Log::error("用户{$user->user_id}密码{$password}更新失败！");
        }
    }

    public function verifyPassword(UserContract $user, $password)
    {
        $credentials['password'] = $password;
        return auth()->getProvider()->validateCredentials($user , $credentials);
    }


    public function updatePassword($request)
    {
        $auth = auth()->user();
        $password = strval($request->input('password' , ""));
        $new_password = strval($request->input('new_password' , ""));
        $password_confirmation = strval($request->input('password_confirmation' , ""));
        $validationField = array(
            'password'=>$password,
            'new_password'=>$new_password,
            'password_confirmation'=>$password_confirmation,
        );
        $rule = [
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
            'new_password'=>[
                'bail',
                'required',
                'string',
                'min:6',
                'max:32'
            ],
            'password_confirmation' => 'bail|required|string|same:new_password',
        ];
        Validator::make($validationField, $rule)->validate();
        $auth->user_pwd = $new_password;
        $auth->save();
    }



    public function updatePhone($request)
    {
        $auth = auth()->user();
        $userId = $auth->user_id;
        $key = 'helloo:account:service:account-update-phone-sms-code:'.$userId;
        $user_phone = ltrim(ltrim(strval($request->input('user_phone')) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $code = strval($request->input('code' , ""));
        $validationField = array(
            'phone'=>$user_phone_country.$user_phone,
            'code'=>$code
        );
        $rule = [
            'phone'=>[
                'bail',
                'required',
                new UserPhone(),
                function ($attribute, $value, $fail) use ($user_phone_country , $user_phone){
                    $phone = DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
                    if(!blank($phone))
                    {
                        $fail(trans('validation.custom.phone.unique'));
                    }
                }
            ],
            'code'=>[
                'bail',
                'required',
                'string',
                'size:4',
                function ($attribute, $value, $fail) use ($key){
                    if(!Redis::exists($key)||$value!=Redis::get($key))
                    {
                        $fail('Verification code error');
                    }
                },
            ]
        ];
        Validator::make($validationField, $rule)->validate();
        Redis::del($key);
        $userPhone = DB::table('users_phones')->where('user_id', $userId)->first();
        if(!empty($userPhone))
        {
            $data = array(
                'user_phone'=>$user_phone,
                'user_phone_country'=>$user_phone_country,
            );
            DB::table('users_phones')->where('user_id', $userId)->update($data);
        }else{
            $data = array(
                'user_phone'=>$user_phone,
                'user_phone_country'=>$user_phone_country,
            );
            DB::table('users_phones')->insert(array_merge(array('user_id'=>$userId) , $data));
        }
    }




    public function updateMessages()
    {
        return [
        ];
    }

    public function updateRules()
    {
        return array(
//            'country_code'=>[
//                'bail',
//                'filled',
//                'string',
//                function ($attribute, $value, $fail) {
//                    $value = strtoupper($value);
//                    if (!in_array($value , config('countries'))) {
//                        $fail(trans('validation.regex'));
//                    }
//                },
//            ],
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
                'max:32'
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
                'string',
                Rule::in(array(
                    'default_avatar_1.png',
                    'default_avatar_2.png',
                    'default_avatar_3.png',
                    'default_avatar_4.png',
                    'default_avatar_5.png',
                    'default_avatar_6.png',
                    'default_avatar_7.png',
                    'default_avatar_8.png',
                    'default_avatar_9.png',
                    'default_avatar_10.png',
                    'default_avatar_11.png',
                    'default_avatar_12.png',
                    'default_avatar_13.png',
                    'default_avatar_14.png',
                    'default_avatar_15.png',
                    'default_avatar_16.png',
                    'default_avatar_17.png',
                    'default_avatar_18.png',
                ))
            ],
            'user_pwd'=>[
                'bail',
                'filled',
                'string',
                'min:6',
                'max:32'
            ],
        );
    }


    private function sendForgetPwdPhoneCode($request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone')) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $key = 'helloo:account:service:account-reset-password-sms-code:'.$user_phone_country.$user_phone;
        $rule = [
            'phone' => [
                'bail',
                'required',
                new UserPhone(),
            ]
        ];
        $validationField = array('phone'=>$user_phone_country.$user_phone);
        Validator::make($validationField, $rule)->validate();
        $userPhone = DB::table('users_phones')->where("user_phone_country" , $user_phone_country)->where("user_phone" , $user_phone)->first();
        if(!blank($userPhone))
        {
            $code = $this->getCode();
            $phone = new PhoneNumber($user_phone , $user_phone_country);
            $message = new ForgetPasswordMessage($code);
            EasySms::dispatch($phone , $message)->onConnection('redis')->onQueue('helloo_{forget_pwd_sms}');
            return $code;
        }
        return;

    }

    public function sendUpdatePhoneCode($request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone')) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $key = 'helloo:account:service:account-update-phone-sms-code:'.$user_phone_country.$user_phone;
        $validationField = array(
            'phone'=>$user_phone_country.$user_phone,
        );
        $rule = [
            'phone' => [
                'bail',
                'required',
                new UserPhone(),
                function ($attribute, $value, $fail) use ($user_phone_country , $user_phone){
                    $phone = DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
                    if(!blank($phone))
                    {
                        $fail(trans('validation.custom.phone.unique'));
                    }
                }
            ]
        ];
        Validator::make($validationField, $rule)->validate();
        $code = $this->getCode();
        $phone = new PhoneNumber($user_phone , $user_phone_country);
        $message = new UpdatePhoneMessage($code);
        EasySms::dispatch($phone , $message)->onConnection('redis')->onQueue('helloo_{update_phone_sms}');
        return $code;
    }

    public function sendSignInPhoneCode($request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone')) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $key = 'helloo:account:service:account-sign-in-sms-code:'.$user_phone_country.$user_phone;
        $validationField = array(
            'phone'=>$user_phone_country.$user_phone,
        );
        $rule = [
            'phone' => [
                'bail',
                'required',
                new UserPhone()
            ],
        ];
        Validator::make($validationField, $rule)->validate();
        $code = $this->getCode();
        $phone = new PhoneNumber($user_phone , $user_phone_country);
        $message = new SignInMessage($code);
        EasySms::dispatch($phone , $message)->onConnection('redis')->onQueue('helloo_{sign_in_sms}');
        return $code;
    }

    public function activate(User $user ,$data)
    {
        $flag = false;
        $now = Carbon::now();
        $userId = $user->getKey();
        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        $key = 'helloo:account:service:account-activation';
        $userKey = "helloo:account:service:account:".$user->getKey();
        if($user->user_activation==0){
            $data['user_activation'] = 1;
            $data['user_activated_at'] = $now->toDateTimeString();
            DB::beginTransaction();
            try{
                $result = DB::table('users')->where('user_id' , $userId)->update($data);
                $res = Redis::zadd($key , $now->timestamp , $userId);
                if($res<=0||$result<=0)
                {
                    Redis::zrem($key , $userId);
                    throw new \Exception('Sorry, your account activation failed！');
                }
                Redis::del($userKey);
                isset($data['user_gender'])&&Redis::zadd($genderSortSetKey , intval($data['user_gender']) , $userId);
                $flag = true;
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Redis::zrem($key , $userId);
                Log::error('account_activation_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            }
        }else{
            $flag = true;
        }
        return $flag;
    }

    public function getCode()
    {
        return (new RandomStringGenerator('1234567890'))->generate();
    }
}