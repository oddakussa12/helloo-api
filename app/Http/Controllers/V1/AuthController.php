<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Jobs\Device;
use Ramsey\Uuid\Uuid;
use App\Rules\UserPhone;
use App\Events\SignupEvent;
use App\Events\SignInEvent;
use Jenssegers\Agent\Agent;
use App\Jobs\SignUpOrInFail;
use Illuminate\Http\Request;
use App\Jobs\Bitrix24Company;
use App\Rules\UserPhoneUnique;
use Illuminate\Validation\Rule;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Foundation\Auth\User\Update;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Validation\ValidationException;
use Dingo\Api\Exception\StoreResourceFailedException;

class AuthController extends BaseController
{

    use Update;

    protected $user;

    /**
     * AuthController constructor.
     * @param UserRepository $user
     */
    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    /**
     * @note 登录
     * @datetime 2021-07-12 18:07
     * @param Request $request
     * @return void
     * @throws ValidationException
     */
    public function signIn(Request $request)
    {
        $agent = new Agent();
        $version = $agent->getHttpHeader('HellooVersion');
        $deviceId = $agent->getHttpHeader('deviceId');
        if(!empty($version)&&version_compare($version , config('common.block_version') , '<='))
        {
            abort(401 , __('Please update to the latest version from Play Store.'));
        }
        if(!empty($deviceId))
        {
            $flag = Redis::sismember('helloo:account:service:block-device' , $deviceId);
            $flag && abort(401 , trans('auth.user_device_banned'));
        }

        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
        $password = $request->input('password');
        $validationField = array(
            'phone'=>$user_phone_country.$user_phone,
            'password'=>$password,
        );
        $rule = [
            'phone' => [
                'bail',
                'required',
                new UserPhone()
            ],
            'password' => [
                'bail',
                'required',
                'string',
                'min:6',
                'max:32',
            ],
        ];
        try{
            Validator::make($validationField, $rule)->validate();
        }catch (ValidationException $exception)
        {
            $errorJob = new SignUpOrInFail($exception->errors());
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            throw new ValidationException($exception->validator);
        }
        $phone = DB::table('users_phones')->where('user_phone_country' ,  $user_phone_country)->where('user_phone' ,  $user_phone)->first();
        if(empty($phone))
        {
            return $this->response->errorUnauthorized(__("Phone number hasn't been registered yet."));
        }

        $user = $this->user->find($phone->user_id);
        if(!config('common.is_verification')||password_verify($password, $user->user_pwd))
        {
            if($user->user_shop==1&&$user->user_verified!=1)
            {
                $message = 'Your store’s review failed or is under review!';
                $errorJob = new SignUpOrInFail($message);
                $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
                return $this->response->errorForbidden($message);
            }
            $token = auth()->login($user);
            $addresses = getRequestIpAddress();
            event(new SignInEvent($user , $addresses));
            return $this->respondWithToken($token , false);
        }
        $errorJob = new SignUpOrInFail(trans('auth.phone_failed'));
        $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
        return $this->response->errorUnauthorized(trans('auth.phone_failed'));
    }

    /**
     * @note 退出
     * @datetime 2021-07-12 18:07
     * @return \Dingo\Api\Http\Response
     */
    public function signOut()
    {
        if(auth()->check())
        {
            auth()->logout();
        }
        return $this->response->noContent();
    }

    /**
     * @note 用户信息填充
     * @datetime 2021-07-12 18:07
     * @param Request $request
     * @return UserCollection
     */
    public function fill(Request $request)
    {
        $fields = array();
        $user = auth()->user();
        $password = strval($request->input('password' , ''));
        $user_birthday = strval($request->input('user_birthday' , ''));
        $user_about = strval($request->input('user_about' , ''));
        $user_avatar = strval($request->input('user_avatar' , ''));
        $user_gender = $request->input('user_gender');
        $user_nick_name = mb_substr(strval($request->input('user_nick_name' , '')) , 0 , 64);
        $school = strval($request->input('school' , ''));
        $user_school = strval($request->input('user_school' , ''));
        $user_grade = strval($request->input('user_grade' , ''));
        $user_country = strval($request->input('user_country' , ''));
        if(!empty($password)&&empty($user->getAuthPassword()))
        {
            $fields['user_pwd'] = $password;
        }
        if(!empty($user_country))
        {
            $fields['user_country'] = $user_country;
        }
        if(!empty($user_birthday)&&$user->user_birthday=="1900-01-01")
        {
            $fields['user_birthday'] = $user_birthday;
        }
        if(!empty($user_avatar)&&$user->user_avatar=='default_avatar.jpg')
        {
            $fields['user_avatar'] = $user_avatar;
        }

        if($user_gender!==null&&in_array($user_gender , array(0 , 1 , '0' , '1')))
        {
            $fields['user_gender'] = intval($user_gender);
        }

        if(!blank($user_nick_name))
        {
            $fields['user_nick_name'] = strval($user_nick_name);
        }

        if(!blank($user_school)&&empty($user->user_school))
        {
            $fields['user_school'] = strval($user_school);
        }

        if(!blank($school))
        {
            $fields['user_sl'] = strval($school);
        }else{
            if(isset($fields['user_school']))
            {
                $school = DB::table('schools')->where('key' , $fields['user_school'])->first();
                if(!blank($school))
                {
                    $fields['user_sl'] = strval($school->name);
                }
            }
        }

        if(!blank($user_grade)&&empty($user->user_grade))
        {
            $fields['user_grade'] = strval($user_grade);
        }

        $fields = array_filter($fields , function($value){
            return !blank($value);
        });

        if(!empty($fields))
        {
            if(!empty($user_about)&&empty($user->user_about))
            {
                $fields['user_about'] = $user_about;
            }
            Validator::make($fields, $this->updateRules())->validate();
            if($user->user_activation==0)
            {
                isset($fields['user_pwd'])&&$fields['user_pwd'] = bcrypt($fields['user_pwd']);
                if(isset($fields['user_nick_name']))
                {
                    $this->activate($user , $fields);
                }
            }
        }
        $user = $this->user->find($user->getKey());
        $genderKey = 'helloo:account:service:account-gender';
        $userGenderChanged = Redis::zscore($genderKey , $user->getKey());
        $user->userGenderChanged = $userGenderChanged===null||$userGenderChanged===false;
        return new UserCollection($user);
    }

    /**
     * @note 用户(商家)信息更新
     * @datetime 2021-07-12 18:39
     * @param Request $request
     * @return UserCollection
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        if(empty($user->user_shop))
        {
            return $this->updateUser($user , $request);
        }else{
            return $this->updateShop($user , $request);
        }
    }

    /**
     * @note 用户信息更新
     * @datetime 2021-07-12 18:39
     * @param $user
     * @param $request
     * @return UserCollection
     */
    private function updateUser($user , $request)
    {
        $fields = array();
        $oldGender = $user->user_gender;
        $genderKey = 'helloo:account:service:account-gender';
        $user_birthday = strval($request->input('user_birthday' , ''));
        $user_about = mb_substr(strval($request->input('user_about' , '')) , 0 , 100);
        $user_avatar = strval($request->input('user_avatar' , ''));
        $user_gender = $request->input('user_gender');
        $user_nick_name = mb_substr(strval($request->input('user_nick_name' , '')) , 0 , 32);
        $user_school = strval($request->input('user_school' , ''));
        $school = strval($request->input('school' , ''));
        $user_grade = strval($request->input('user_grade' , ''));
        $user_enrollment_at = strval($request->input('user_enrollment_at' , ''));
        $user_country = strval($request->input('user_country' , ''));
        $user_bg = strval($request->input('user_bg' , ''));
        if(!empty($user_country))
        {
            $fields['user_country'] = $user_country;
        }
        if(!empty($user_birthday))
        {
            $fields['user_birthday'] = $user_birthday;
        }
        if(!empty($user_enrollment_at))
        {
            $fields['user_enrollment_at'] = $user_enrollment_at;
        }
        if(!empty($user_avatar))
        {
            $fields['user_avatar'] = $user_avatar;
        }
        if(!empty($user_about))
        {
            $fields['user_about'] = $user_about;
        }
        if($user_gender!==null&&in_array($user_gender , array(0 , 1 , '0' , '1')))
        {
            $fields['user_gender'] = intval($user_gender);
        }
        if(!blank($user_nick_name))
        {
            $fields['user_nick_name'] = strval($user_nick_name);
        }
        if(!blank($user_school))
        {
            $fields['user_school'] = $user_school;
        }
        if(!blank($school))
        {
            $fields['user_sl'] = $school;
        }else{
            if(isset($fields['user_school']))
            {
                $school = DB::table('schools')->where('key' , $fields['user_school'])->first();
                if(!blank($school))
                {
                    $fields['user_sl'] = strval($school->name);
                }
            }
        }
        if(!blank($user_grade))
        {
            $fields['user_grade'] = $user_grade;
        }
        if(!blank($user_bg))
        {
            $fields['user_bg'] = $user_bg;
        }
        $fields = array_filter($fields , function($value){
            return !blank($value);
        });
        if(!empty($fields)&&$user->user_activation==1)
        {
            Validator::make($fields, $this->updateRules())->validate();
            $user = $this->user->update($user , $fields);
            if($user->user_gender!=$oldGender)
            {
                Redis::zadd($genderKey , time() , $user->getKey());
            }
        }
        return new UserCollection($user);
    }

    /**
     * @note 商家信息更新
     * @datetime 2021-07-12 18:39
     * @param $user
     * @param $request
     * @return UserCollection
     */
    private function updateShop($user , $request)
    {
        $fields = array();
        $user_avatar = strval($request->input('user_avatar' , ''));
        $user_bg = strval($request->input('user_bg' , ''));
        $user_nick_name = mb_substr(strval($request->input('user_nick_name' , '')) , 0 , 64);
        $user_name = mb_substr(strval($request->input('user_name' , '')) , 0 , 24);
        $user_address = mb_substr(strval($request->input('user_address' , '')) , 0 , 512);
        $user_contact = mb_substr(strval($request->input('user_contact' , '')) , 0 , 64);
        $user_about = strval($request->input('user_about' , ''));
        $user_currency = strval($request->input('user_currency' , ''));
        if(!empty($user_avatar)&&$user->user_avatar!=$user_avatar)
        {
            $fields['user_avatar'] = $user_avatar;
        }
        if(!blank($user_bg)&&$user->user_bg!=$user_bg)
        {
            $fields['user_bg'] = $user_bg;
        }
        if(!empty($user_nick_name)&&$user->user_nick_name!=$user_nick_name)
        {
            $fields['user_nick_name'] = $user_nick_name;
        }
        if(!blank($user_name)&&strtolower($user->user_name)!=strtolower($user_name))
        {
            $fields['user_name'] = $user_name;
            $fields['user_name_change'] = "user_name_change+1";
            $fields['user_name_changed_at'] = date('Y-m-d H:i:s');
        }
        if(!blank($user_address)&&$user->user_address!=$user_address)
        {
            $fields['user_address'] = $user_address;
        }
        if(!blank($user_contact)&&$user->user_contact!=$user_contact)
        {
            $fields['user_contact'] = $user_contact;
        }
        if(!empty($user_about)&&$user->user_about!=$user_about)
        {
            $fields['user_about'] = $user_about;
        }
        if(!empty($user_currency)&&empty($user->user_currency))
        {
            $fields['user_currency'] = $user_currency;
        }
        $fields = array_filter($fields , function($value){
            return !blank($value);
        });
        if(!empty($fields)&&$user->user_activation==1)
        {
            $rules = array(
                'user_avatar'=>[
                    'bail',
                    'filled',
                    'string',
                    'min:30',
                    'max:300'
                ],
                'user_bg'=>[
                    'bail',
                    'filled',
                    'string',
                    'min:30',
                    'max:300'
                ],
                'user_nick_name'=>[
                    'bail',
                    'filled',
                    'string',
                    'min:2',
                    'max:32'
                ],
                'user_name'=>[
                    'bail',
                    'filled',
                    'string',
                    'alpha_num',
                    'min:3',
                    'max:24',
                    function ($attribute, $value, $fail) use ($user){
                        $index = ($user->user_id)%2;
                        $usernameKey = 'helloo:account:service:account-username-'.$index;
                        if(Redis::sismember($usernameKey , strtolower($value)))
                        {
                            $fail(__('Nickname taken already.'));
                        }
                        $exist = DB::table('users')->where('user_name' , $value)->first();
                        if(!blank($exist))
                        {
                            $fail(__('Nickname taken already.'));
                        }
                    },
                ],
                'user_address'=>[
                    'bail',
                    'filled',
                    'string',
                    'min:10',
                    'max:100',
                ],

                'user_contact'=>[
                    'bail',
                    'filled',
                    'numeric',
                    'max:99999999999999'
                ],
                'user_about' => [
                    'bail',
                    'filled',
                    'string',
                    'max:100'
                ],
                'user_currency' => [
                    'bail',
                    'filled',
                    'string',
                    Rule::in(array('USD' , 'BIRR'))
                ],
            );
            Validator::make($fields, $rules)->validate();
            $user = $this->user->update($user , $fields);
            $this->dispatch((new Bitrix24Company($user , 'update'))->onQueue('helloo_{bitrix_company}'));
        }
        return new UserCollection($user);
    }

    /**
     * @note token
     * @datetime 2021-07-12 18:39
     * @param $token
     * @param bool $extend
     * @return mixed
     */
    protected function respondWithToken($token , $extend=true)
    {
        $user = auth()->user();
        $referer = request()->input('referer' , 'web');
        if($referer!='web')
        {
            $deviceFields = request()->all();
            $deviceFields['referer'] = $referer;
            $device = new Device($deviceFields , 'signUpOrIn');
            $this->dispatch($device->onQueue('registered_plant'));
        }
        $token = array(
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        );
        if($extend)
        {
            $user = array(
                    'user_id'=>$user->user_id,
                    'user_nick_name'=>$user->user_nick_name,
                    'user_avatar'=>$user->user_avatar_link,
                    'user_gender'=>$user->user_gender,
            );
            $token['user'] = $user;
        }
        return $this->response->array($token);
    }

    /**
     * @note credentials 查询
     * @datetime 2021-07-12 18:39
     * @param $request
     * @return array
     */
    protected function credentials($request)
    {
        $user_fields = $request->only($this->username(), 'password');
        $credentials['password'] = $user_fields['password'];
        $supportFields = array($this->user->getDefaultEmailField() , $this->user->getDefaultNameField());
        foreach ($supportFields as $field) {
            if (empty($user_fields[$field])) {
                $credentials[$field] = $user_fields[$this->username()];
            }
        }
        return $credentials;
    }

    /**
     * @note 用户信息
     * @datetime 2021-07-12 18:40
     * @return UserCollection
     */
    public function me()
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $likedKey = 'helloo:account:service:account-liked-num';
        $user->likedCount = intval(Redis::zscore($likedKey , $userId));
        $user->friendCount = 0;
        $genderKey = 'helloo:account:service:account-gender';
        $userGenderChanged = Redis::zscore($genderKey , $userId);
        $user->userGenderChanged = $userGenderChanged===null||$userGenderChanged===false;
        $userNameKey = 'helloo:account:service:account-username-change';
        $time = Redis::zscore($userNameKey , $userId);
        $user->userNameCanChange = $time===null||$time===false;

        $privacy = app(UserRepository::class)->findPrivacyByUserId($userId);
        $user->privacy = $privacy;

        // 积分 排行
        $memKey = 'helloo:account:user-score-rank';
        $rank   = Redis::zrevrank($memKey , $userId);
        $rank   = !empty($rank) ? $rank : Redis::zcard($memKey);
        $user->rank  = (int)$rank+1;
        $user->score = (int)Redis::zscore($memKey , $userId);

        $user->userNamePrompted = boolval(app(UserRepository::class)->usernamePrompt($userId));
        $user->makeVisible(array('user_name_changed_at'));
        if($user->user_shop==1)
        {
            $point = app(UserRepository::class)->findPointByUserId($userId);
            $user->userPoint = $point;
        }
        return new UserCollection($user);
    }

    /**
     * @note 用户名
     * @datetime 2021-07-12 18:40
     * @return string
     */
    public function username()
    {
        return 'name';
    }

    /**
     * @note 手机号注册
     * @datetime 2021-07-12 18:40
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function phoneSignUp(Request $request)
    {
        $agent = new Agent();
        $version = $agent->getHttpHeader('HellooVersion');
        $deviceId = $agent->getHttpHeader('deviceId');
        if(!empty($version)&&version_compare($version , config('common.block_version') , '<='))
        {
            abort(401 , __('Please update to the latest version from Play Store.'));
        }
        if(!empty($deviceId))
        {
            $flag = Redis::sismember('helloo:account:service:block-device' , $deviceId);
            $flag && abort(401 , trans('auth.user_device_banned'));
        }

        $password = strval($request->input('password' , ""));
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
        $gps = strval($request->input('gps' , ''));
//        $code = strval($request->input('code' , ''));
        $phone = $user_phone_country.$user_phone;
        $validationField = array(
            'user_phone'=>$phone,
//            'code'=> $code,
            'password'=> $password,
        );
        $rule = [
            'user_phone' => [
                'bail',
                'string',
                'required',
                new UserPhone()
            ],
            'password' => 'bail|required|string|min:6|max:16',

        ];
        try{
            Validator::make($validationField, $rule)->validate();
        }catch (ValidationException $exception)
        {
            $errorJob = new SignUpOrInFail($exception->errors());
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            throw new ValidationException($exception->validator);
        }
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
        $userPhone = DB::table('users_phones')->where('user_phone_country' ,  $user_phone_country)->where('user_phone' ,  $user_phone)->first();
        if(!empty($userPhone))
        {
            $errorJob = new SignUpOrInFail(trans('validation.custom.phone.unique'));
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            abort(422 , trans('validation.custom.phone.unique'));
        }
        $now = Carbon::now()->toDateTimeString();
        if($agent->match('HellooAndroid'))
        {
            $src = 'android';
        }elseif($agent->match('HellooiOS')){
            $src = 'ios';
        }else{
            $src = 'unknown';
        }
        $password = empty($password)?Uuid::uuid1()->toString():$password;
        $username = $uuid = $this->generateUniqueName();
        $userId = $this->generateUserId();
        $user_fields = array(
            'user_id'=>$userId,
            'user_src'=>$src,
            'user_name'=>$username,
            'user_created_at'=>$now,
            'user_updated_at'=>$now,
            'user_uuid'=>$uuid,
            'user_currency'=>$user_phone_country=='251'?'BIRR':'USD',
            'user_pwd'=>bcrypt($password)
        );
        DB::beginTransaction();
        try{
            DB::table('users')->insert($user_fields);
            DB::table('users_phones')->insert(array('user_id'=>$userId , 'user_phone'=>$user_phone , 'user_phone_country'=>$user_phone_country));
            DB::commit();
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('sign_up_fail' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
            $errorJob = new SignUpOrInFail($e->getMessage());
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            throw new StoreResourceFailedException('sign up failed');
        }
        $user = $this->user->find($userId);
        if(intval($user_phone)%2==0)
        {
            $phoneKey = "helloo:account:service:account-phone-{even}-number";
        }else{
            $phoneKey = "helloo:account:service:account-phone-{odd}-number";
        }
        Redis::zadd($phoneKey , $userId , $phone);
        $addresses = getRequestIpAddress();
        event(new SignupEvent($user , $addresses , array(
            'user_phone'=>$user_phone,
            'user_phone_country'=>$user_phone_country,
            'gps'=>$gps,
        )));
        $token = auth()->login($user);
        return $this->respondWithToken($token , false);
    }

    /**
     * @note 注册
     * @datetime 2021-07-12 18:40
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function handleSignIn(Request $request)
    {
        $agent = new Agent();
        $version = $agent->getHttpHeader('HellooVersion');
        $deviceId = $agent->getHttpHeader('deviceId');
        if(!empty($version)&&version_compare($version , config('common.block_version') , '<='))
        {
            abort(401 , __('Please update to the latest version from Play Store.'));
        }
        if(!empty($deviceId))
        {
            $flag = Redis::sismember('helloo:account:service:block-device' , $deviceId);
            $flag && abort(401 , trans('auth.user_device_banned'));
        }

//        $password = strval($request->input('password' , ""));
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
        $code = strval($request->input('code' , ''));
        $phone = $user_phone_country.$user_phone;
        $validationField = array(
            'user_phone'=>$phone,
            'code'=> $code,
//            'password'=> $password,
        );
        $rule = [
            'user_phone' => [
                'bail',
                'string',
                'required',
                new UserPhone()
            ],
//            'password' => 'bail|required|string|min:6|max:16',
            'code' => [
                'bail',
                'string',
                'required',
                'size:4',
                function ($attribute, $value, $fail) use ($phone){
                    $key = 'helloo:account:service:account-sign-in-sms-code:'.$phone;
                    $code = Redis::get($key);
                    if($code===null||$code!=$value)
                    {
                        config('common.is_verification')&&$fail(trans('validation.custom.code.error'));
                    }else{
                        Redis::del($key);
                    }
                },
            ]
        ];
        try{
            Validator::make($validationField, $rule)->validate();
        }catch (ValidationException $exception)
        {
            $errorJob = new SignUpOrInFail($exception->errors());
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            throw new ValidationException($exception->validator);
        }
        $phone = DB::table('users_phones')->where('user_phone_country' ,  $user_phone_country)->where('user_phone' ,  $user_phone)->first();
        if(!empty($phone))
        {
            $user = $this->user->find($phone->user_id);
//            if(!config('common.is_verification')||!password_verify($password, $user->user_pwd))
//            {
//                return $this->response->errorUnauthorized(trans('auth.phone_failed'));
//            }
            $token = auth()->login($user);
            return $this->respondWithToken($token , false);
        }
        $now = Carbon::now()->toDateTimeString();
        $agent = new Agent();
        if($agent->match('HellooAndroid'))
        {
            $src = 'android';
        }elseif($agent->match('HellooiOS')){
            $src = 'ios';
        }else{
            $src = 'unknown';
        }
//        $password = empty($password)?Uuid::uuid1()->toString():$password;
        $username = $uuid = $this->generateUniqueName();
        $userId = $this->generateUserId();
        $user_fields = array(
            'user_id'=>$userId,
            'user_src'=>$src,
            'user_name'=>$username,
            'user_uuid'=>$uuid,
            'user_created_at'=>$now,
            'user_updated_at'=>$now,
            'user_pwd'=>bcrypt($userId)
        );
        DB::beginTransaction();
        try{
            DB::table('users')->insert($user_fields);
            DB::table('users_phones')->insert(array('user_id'=>$userId , 'user_phone'=>$user_phone , 'user_phone_country'=>$user_phone_country));
            DB::commit();
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('sign_up_fail' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
            $errorJob = new SignUpOrInFail($e->getMessage());
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            throw new StoreResourceFailedException('sign up failed');
        }
        $user = $this->user->find($userId);
        $addresses = getRequestIpAddress();
        event(new SignupEvent($user , $addresses , array(
            'user_phone'=>$user_phone,
            'user_phone_country'=>$user_phone_country,
        )));
        $token = auth()->login($user);
        return $this->respondWithToken($token , false);
    }

    /**
     * @note 注册验证
     * @datetime 2021-07-12 18:41
     * @param $account
     * @param $type
     * @return \Dingo\Api\Http\Response|void
     * @throws ValidationException
     */
    public function accountVerification($account , $type)
    {
        $response = $this->response;
        if(in_array($type , array('phone' , 'nick_name')))
        {
            $type = strtolower($type);
            if($type=='phone'){
                $type = 'user_'.$type;
                $rule = [
                    $type => [
                        'bail',
                        'required',
                        new UserPhone(),
                    ],
                ];
            }else{
                $type = 'user_'.$type;
                $rule = [
                    $type => [
                        'bail',
                        'required',
                        'min:4',
                        'max:32'
                    ],
                ];
            }
            try{
                Validator::make(array($type=>$account), $rule)->validate();
            }catch (ValidationException $exception)
            {
                $errorJob = new SignUpOrInFail($exception->errors() , array('account'=>$account));
                $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
                throw new ValidationException($exception->validator);
            }
            if($type=='user_phone')
            {
                $existRule = [
                    $type => [
                        new UserPhoneUnique()
                    ],
                ];
                $validator = Validator::make(array($type=>$account), $existRule)->fails();
                $response = $response->accepted(null , array(
                    'Signed-in'=>intval($validator)
                ))->withHeader('Signed-in' , intval($validator));
                if(intval($validator)==1)
                {
                    $errorJob = new SignUpOrInFail(strval($validator) , array('account'=>$account));
                    $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
                }
            }else{
                $response = $response->accepted();
            }
        }else{
            $response = $response->errorMethodNotAllowed();
        }
        return $response;
    }

    /**
     * @note 用户名验证
     * @datetime 2021-07-12 18:41
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function accountNameVerification(Request $request)
    {
        $username = $request->input('user_name' , '');
        $validationField = array(
            'user_name' => $username
        );
        $rules = array(
            'user_name' => [
                'bail',
                'required',
                'string',
                'alpha_num',
                'between:3,24',
                function ($attribute, $value, $fail){
                    $exist = DB::table('users')->where('user_name' , $value)->first();
                    if(!blank($exist))
                    {
                        $fail(__('Nickname taken already.'));
                    }
                }
            ],
        );
        $validator = Validator::make($validationField, $rules);
        if($validator->fails())
        {
            $errors = $validator->errors()->first('user_name');
            abort(422 , $errors);
        }
        return $this->response->noContent();
    }

    /**
     * @note 密码重置
     * @datetime 2021-07-12 18:41
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function resetPwdByPhone(Request $request)
    {
        $this->resetByPhone($request);
        return $this->response->noContent();
    }

    /**
     * @note 忘记密码
     * @datetime 2021-07-12 18:41
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function forgetPwdCode(Request $request)
    {
        $code = $this->sendForgetPwdPhoneCode($request);
        return $this->response->created();
    }

    /**
     * @note 密码更新
     * @datetime 2021-07-12 18:41
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function password(Request $request)
    {
        $this->updatePassword($request);
        return $this->response->accepted();
    }

    /**
     * @note 验证验证码
     * @datetime 2021-07-12 18:42
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function newPhoneCode(Request $request)
    {
        $code = $this->sendUpdatePhoneCode($request);
        return $this->response->created();
    }

    /**
     * @note 验证码登录
     * @datetime 2021-07-12 18:43
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function signInPhoneCode(Request $request)
    {
        $code = $this->sendSignInPhoneCode($request);
        return $this->response->created();
    }

    /**
     * @note 密码验证
     * @datetime 2021-07-12 18:43
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function verifyAuthPassword(Request $request)
    {
        $auth = auth()->user();
        $password = strval($request->input('password' , ''));
        $validationField = array(
            'password'=>$password
        );
        $rule = array(
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
        );
        Validator::make($validationField, $rule)->validate();
        return $this->response->noContent();
    }

    /**
     * @note 更新手机号
     * @datetime 2021-07-12 18:43
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function updateAuth(Request $request)
    {
        $this->updatePhone($request);
        return $this->response->accepted();
    }

    /**
     * @note 更新用户名
     * @datetime 2021-07-12 18:43
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function updateName(Request $request)
    {
        $this->updateUserName($request);
        return $this->response->accepted();
    }

    /**
     * @note 用户名弹窗
     * @datetime 2021-07-12 18:44
     * @return \Dingo\Api\Http\Response
     */
    public function usernamePrompt()
    {
        $userId = auth()->id();
        app(UserRepository::class)->updateUsernamePrompt($userId);
        return $this->response->created();
    }

    /**
     * @note 注册
     * @datetime 2021-07-12 18:44
     * @param Request $request
     * @return mixed|void
     * @throws ValidationException
     */
    public function signUp(Request $request)
    {
        $agent = new Agent();
        $version = $agent->getHttpHeader('HellooVersion');
        $deviceId = $agent->getHttpHeader('deviceId');
        if(!empty($version)&&version_compare($version , config('common.block_version') , '<='))
        {
            abort(401 , __('Please update to the latest version from Play Store.'));
        }
        if(!empty($deviceId))
        {
            $flag = Redis::sismember('helloo:account:service:block-device' , $deviceId);
            $flag && abort(401 , trans('auth.user_device_banned'));
        }

        $registration_type = strval($request->input('registration_type' , 'user'));
        $user_name = strval($request->input('user_name' , ''));
        $user_nick_name = strval($request->input('user_nick_name' , ''));
        $password = strval($request->input('password' , ""));
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
        $gps = strval($request->input('gps' , ''));
//        $code = strval($request->input('code' , ''));
        $phone = $user_phone_country.$user_phone;
        $validationField = array(
            'user_phone'=>$phone,
//            'code'=> $code,
            'password'=> $password,
            'user_nick_name'=> $user_nick_name,
        );
        $rule = [
            'user_phone' => [
                'bail',
                'string',
                'required',
                new UserPhone()
            ],
            'password' => 'bail|required|string|min:6|max:16',
            'user_nick_name' => 'bail|required|string|min:1|max:64',
        ];
        if(!empty($user_name))
        {
            $validationField['user_name'] = $user_name;
            $rule['user_name'] = [
                'bail',
                'required',
                'string',
                'alpha_num',
                'between:3,24',
                function ($attribute, $value, $fail){
                    if(!preg_match('/^[a-z0-9]+$/iu', $value))
                    {
                        $fail('The username can only contain numbers and letters.');
                    }
                    $exist = DB::table('users')->where('user_name' , $value)->first();
                    if(!blank($exist))
                    {
                        $fail(__('Nickname taken already.'));
                    }
                }
            ];
        }
        try{
            Validator::make($validationField, $rule)->validate();
        }catch (ValidationException $exception)
        {
            $errorJob = new SignUpOrInFail($exception->errors());
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            throw new ValidationException($exception->validator);
        }
        if($user_phone_country=='62'&&substr($user_phone , 0 , 2)=='62')
        {
            $user_phone = substr($user_phone , 2);
        }
        $userPhone = DB::table('users_phones')->where('user_phone_country' ,  $user_phone_country)->where('user_phone' ,  $user_phone)->first();
        if(!empty($userPhone))
        {
            $errorJob = new SignUpOrInFail(trans('validation.custom.phone.unique'));
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            abort(422 , trans('validation.custom.phone.unique'));
        }
        $now = Carbon::now()->toDateTimeString();
        if($agent->match('HellooAndroid'))
        {
            $src = 'android';
        }elseif($agent->match('HellooiOS')){
            $src = 'ios';
        }else{
            $src = 'unknown';
        }
        $password = empty($password)?Uuid::uuid1()->toString():$password;
        $uuid = $this->generateUniqueName();
        $username = empty($user_name)?$uuid:$user_name;
        $userId = $this->generateUserId();
        $user_fields = array(
            'user_id'=>$userId,
            'user_src'=>$src,
            'user_name'=>$username,
            'user_nick_name'=>$user_nick_name,
            'user_created_at'=>$now,
            'user_updated_at'=>$now,
            'user_activated_at'=>$now,
            'user_uuid'=>$uuid,
            'user_activation'=>1,
            'user_currency'=>$user_phone_country=='251'?'BIRR':'USD',
            'user_pwd'=>bcrypt($password)
        );
        if($registration_type=='shop')
        {
            $user_fields['user_shop']=1;
        }else{
            // $user_fields['user_avatar']=null;
            $user_fields['user_verified']=1;
            $user_fields['user_verified_at']=$now;
        }
        DB::beginTransaction();
        try{
            DB::table('users')->insert($user_fields);
            DB::table('users_phones')->insert(array('user_id'=>$userId , 'user_phone'=>$user_phone , 'user_phone_country'=>$user_phone_country));
            DB::commit();
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('sign_up_fail' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
            $errorJob = new SignUpOrInFail($e->getMessage());
            $this->dispatch($errorJob->onQueue('helloo_{sign_up_or_in_error}'));
            throw new StoreResourceFailedException('sign up failed');
        }
        $user = $this->user->find($userId);
        if(intval($user_phone)%2==0)
        {
            $phoneKey = "helloo:account:service:account-phone-{even}-number";
        }else{
            $phoneKey = "helloo:account:service:account-phone-{odd}-number";
        }
        Redis::zadd($phoneKey , $userId , $phone);
        $addresses = getRequestIpAddress();
        event(new SignupEvent($user , $addresses , array(
            'user_phone'=>$user_phone,
            'user_phone_country'=>$user_phone_country,
            'gps'=>$gps,
        )));
        $this->activate($user);
        if($registration_type=='shop')
        {
            return $this->response->errorForbidden('Shop waiting for review!');
        }
        $token = auth()->login($user);
        return $this->respondWithToken($token , false);
    }


}
