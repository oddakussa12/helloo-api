<?php
namespace App\Foundation\Auth\User;

use App\Jobs\UserSyncShop;
use Carbon\Carbon;
use App\Jobs\School;
use App\Models\User;
use App\Jobs\EasySms;
use App\Rules\UserPhone;
use App\Messages\SignInMessage;
use Illuminate\Validation\Rule;
use App\Jobs\UserSynchronization;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Custom\EasySms\PhoneNumber;
use App\Jobs\OneTimeUserScoreUpdate;
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
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
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
                        config('common.is_verification')&&$fail(trans('validation.custom.code.error'));
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
            Log::info("user_update_fail" , array(
                'user_id'=>$user->user_id,
                'password'=>$password,
            ));
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
                'min:2',
                'max:32'
            ],
            'user_birthday'=>[
                'bail',
                'filled',
                'date',
                'after:'.date('Y-m-d' , strtotime('-102years')),
                'before:'.date('Y-m-d' , strtotime('-1years')),
            ],
            'user_enrollment_at'=>[
                'bail',
                'filled',
                'string'
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
                'between:6,32'
            ],
            'user_school'=>[
                'bail',
                'filled',
                'string',
            ],
            'school'=>[
                'bail',
                'filled',
                'string',
                'between:1,250'
            ],
            'user_grade'=>[
                'bail',
                'filled',
                'string',
            ],
        );
    }


    private function sendForgetPwdPhoneCode($request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone')) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $key = 'helloo:account:service:account-reset-password-sms-code:'.$user_phone_country.$user_phone;
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
        $rule = [
            'phone' => [
                'bail',
                'required',
                new UserPhone(),
            ]
        ];
        $validationField = array('phone'=>$user_phone_country.$user_phone);
        Validator::make($validationField, $rule)->validate();
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
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
        $phone = DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
        if(!blank($phone))
        {
            return;
        }
        $code = $this->getCode();
        $phone = new PhoneNumber($user_phone , $user_phone_country);
        $message = new SignInMessage($code);
        EasySms::dispatch($phone , $message)->onConnection('redis')->onQueue('helloo_{sign_in_sms}');
        return $code;
    }

    /**
     * 2021-02-02 9:45
     * @param User $user
     * @param $data
     * @return bool|void
     */
    public function activate(User $user ,$data=array())
    {
        $flag = false;
        $now = Carbon::now();
        $userId = $user->getKey();
        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        $ageSortSetKey = 'helloo:account:service:account-age-sort-set';
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
                isset($data['user_birthday'])&&Redis::zadd($ageSortSetKey , intval(age($data['user_birthday'])) , $userId);
                $flag = true;
                UserSyncShop::dispatch($user , $data)->onQueue('helloo_{user_sync_shop}');
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Redis::zrem($key , $userId);
                Log::info('account_activation_failed' , array(
                    'user_id'=>$userId,
                    'code'=>$e->getCode(),
                    'message'=>$e->getMessage(),
                ));
            }
            if($flag==true&&(isset($data['user_sl'])||isset($data['user_school'])))
            {
                if(isset($data['user_sl']))
                {
                    $school = $data['user_sl'];
                }else{
                    $school = DB::table('schools')->where('key' , $data['user_school'])->first();
                    if(blank($school))
                    {
                        return true;
                    }
                    $school = $school->name;
                }
                $now = Carbon::now()->toDateTimeString();
                $logData = array(
                    'id'=>app('snowflake')->id(),
                    'user_id'=>$userId,
                    'school'=>$school,
                    'created_at'=>$now,
                );
                DB::table('users_schools_logs')->insert($logData);
                School::dispatch($school)->onQueue('helloo_{user_school}');
                if(strval($school)!='other')
                {
                    OneTimeUserScoreUpdate::dispatch($user , 'fillSchool')->onQueue('helloo_{one_time_user_score_update}');
                }else{
                    OneTimeUserScoreUpdate::dispatch($user , 'fillSchoolOther')->onQueue('helloo_{one_time_user_score_update}');
                }
            }
        }else{
            $flag = true;
        }
        if($flag==true)
        {
            UserSynchronization::dispatch($user , 'activation')->onQueue('helloo_{user_synchronization}')->delay(now()->addSeconds(120));
            if(isset($data['user_avatar'])&&$data['user_avatar']!='default_avatar.jpg')
            {
                OneTimeUserScoreUpdate::dispatch($user , 'fillAvatar')->onQueue('helloo_{one_time_user_score_update}');
            }
        }
        return $flag;
    }

    public function getCode()
    {
        return (new RandomStringGenerator('1234567890'))->generate();
    }

    public function updateUserName($request)
    {
        $key = 'helloo:account:service:account-username-change';
        $username = trim(strval($request->input('user_name' , '')));
        if(!blank($username))
        {
            $user = auth()->user();
            $oldName = $user->user_name;
            if($oldName==$username)
            {
                return;
            }
            $index = ($user->user_id)%2;
            $usernameKey = 'helloo:account:service:account-username-'.$index;
            $rules = array(
                'user_name' => [
                    'bail',
                    'required',
                    'string',
                    'alpha_num',
                    'between:1,20',
//                    function ($attribute, $value, $fail) use ($user , $key){
//                        $len = strlen($value);
//                        $mbLen = mb_strlen($value);
//                        if($mbLen!==$len)
//                        {
//                            Log::info('special_characters' , array(
//                                'user_id'=>$user->user_id,
//                                'name'=>$value,
//                            ));
//                            $fail('The username must contain letters or numbers.');
//                        }
//                    },
//                    function ($attribute, $value, $fail) use ($user , $key){
//                        if(preg_match("/^\d*$/",$value)||preg_match("/^[a-z]*$/i",$value))
//                        {
//                            $fail('The username must contain letters and numbers.');
//                        }
//                    },
//                    function ($attribute, $value, $fail) use ($user , $key){
//                        $score = Redis::zscore($key , $user->user_id);
////                        if($score!==null&&Carbon::createFromTimestamp($score)->diffInYears()<1)
//                        if($score!==null)
//                        {
//                            $fail('You can only change your username once within a year!');
//                        }
//                    },
                    function ($attribute, $value, $fail) use ($user , $key){
                        $score = Redis::zscore($key , $user->user_id);
//                        if($score!==null&&Carbon::createFromTimestamp($score)->diffInYears()<1)
                        if($score!==null)
                        {
                            $fail('You can only change your username once within a year!');
                        }
                    },
                    function ($attribute, $value, $fail) use ($usernameKey){
                        if(Redis::sismember($usernameKey , strtolower($value)))
                        {
                            $fail(__('Nickname taken already.'));
                        }
                        $exist = DB::table('users')->where('user_name' , $value)->first();
                        if(!blank($exist))
                        {
                            $fail(__('Nickname taken already.'));
                        }
                        $exist = DB::table('shops')->where('name' , $value)->first();
                        if(!blank($exist))
                        {
                            $fail(__('Nickname taken already.'));
                        }
                    }
                ],
            );
            $validationField = array(
                'user_name' => $username
            );
            Validator::make($validationField, $rules)->validate();
            $key = 'helloo:account:service:account-username-change';
            $now = Carbon::now();
            DB::beginTransaction();
            try {
                $createdAt = $now->toDateTimeString();
                $count = DB::table('users')->where('user_id' , $user->user_id)->increment('user_name_change' , 1 ,
                    array(
                        'user_name'=>$username,
                        'user_name_changed_at'=>$createdAt,
                    )
                );
                $result = DB::table('users_names_logs')->insert(array(
                    'user_id'=>$user->user_id,
                    'user_name'=>$oldName,
                    'created_at'=>$createdAt,
                ));
                if($count>0&&$result)
                {
                    Redis::sadd($usernameKey , strtolower($username));
                    Redis::zadd($key , $now->timestamp , $user->user_id);
                    DB::commit();
                    UserSyncShop::dispatch($user , array() , $username)->onQueue('helloo_{shop_sync_user}');
                }else{
                    throw new \Exception('Database update failed');
                }
                substr($user->user_name , 0 , 3)=='lb_' && OneTimeUserScoreUpdate::dispatch($user , 'fillName')->onQueue('helloo_{one_time_user_score_update}');
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('username_update_fail' , array(
                    'message'=>$e->getMessage(),
                    'user_id'=>$user->user_id,
                    'user_name'=>$user->user_name,
                    'username'=>$username
                ));
            }

        }
    }

    public function generateUniqueName()
    {
        $key = 'helloo:account:service:account-user-name-unique-set';
        $element = Redis::spop($key);
        if($element==null)
        {
            $element = 'lb_'.strval(millisecond());
        }
        DB::table('unique_usernames')->where('username' , $element)->update(array('status'=>1));
        return $element;
    }
    public function generateUserId()
    {
        $key = 'helloo:account:service:account-user-id-unique-set';
        $element = Redis::spop($key);
        if($element==null)
        {
            abort(403 , 'Server Error');
        }
        return $element;
    }
}