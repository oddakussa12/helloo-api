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
        $password = strval($request->input('password'));
        $password_confirmation = strval($request->input('password_confirmation'));
        $rules = [
            'code' => 'bail|required|string|size:4',
            'user_phone' => [
                'bail',
                'required',
                'string',
                new UserPhone()
            ],
            'password' => 'bail|required|string|confirmed|min:6|max:16',
            'password_confirmation' => 'bail|required|string|same:password',
        ];
        $validationField = array(
            'code' => $code,
            'user_phone'=>$user_phone_country.$user_phone,
            'password'=>$password,
            'password_confirmation'=>$password_confirmation,
        );
        Validator::make($validationField, $rules)->validate();
        $user = DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
        if(blank($user))
        {
            abort(404 , 'Account does not exist!');
        }
        $key = 'helloo:account:service:account-reset-password-sms-code:'.$user_phone_country.$user_phone;
        $userCode = strval(Redis::get($key));
        if(empty($code)||empty($userCode)||$code!=$userCode)
        {
            abort(422 , 'Phone verification code error!');
        }
        Redis::del($key);
        $res = DB::table('users')->where($user->user_id)->update(
            array('user_pwd'=>bcrypt($password))
        );
        if($res<=0)
        {
            \Log::error("用户{$user->user_id}密码{$password}更新失败！");
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
            'name.unique'=>'This account already exists'
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
                'string'
            ],
            'user_pwd'=>[
                'bail',
                'filled',
                'string',
                'min:6',
                'max:16'
            ],
        );
    }


    private function sendForgetPwdPhoneCode($request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone')) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $key = 'helloo:account:service:account-reset-password-sms-code:'.$user_phone_country.$user_phone;
        $rule = [
            'user_phone' => [
                'bail',
                'required',
                new UserPhone(),
            ]
        ];
        $validationField = array('user_phone'=>$user_phone_country.$user_phone);
        Validator::make($validationField, $rule)->validate();
        $userPhone = DB::table('users_phones')->where("user_phone_country" , $user_phone_country)->where("user_phone" , $user_phone)->first();
        if(!blank($userPhone))
        {
            $code = $this->getCode();
            $phone = new PhoneNumber($user_phone , $user_phone_country);
            $message = new ForgetPasswordMessage($code);
            EasySms::dispatch($phone , $message)->onQueue('helloo_forget_pwd_sms');
        }

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
        EasySms::dispatch($phone , $message)->onQueue('helloo_update_phone_sms');
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
        EasySms::dispatch($phone , $message)->onQueue('helloo_sign_in_sms');
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