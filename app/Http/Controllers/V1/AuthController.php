<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Sms;
use App\Jobs\Device;
use Ramsey\Uuid\Uuid;
use App\Rules\UserPhone;
use App\Events\SignupEvent;
use Illuminate\Http\Request;
use App\Rules\UserPhoneUnique;
use Illuminate\Support\Carbon;
use App\Events\UserUpdatedEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Custom\Uuid\RandomStringGenerator;
use App\Repositories\Contracts\UserRepository;
use Dingo\Api\Exception\StoreResourceFailedException;

class AuthController extends BaseController
{

    protected $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }



    public function signIn(Request $request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
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
            \Validator::make($validationField, $rule)->validate();
        }catch (\Illuminate\Validation\ValidationException $e)
        {
            throw new \Illuminate\Validation\ValidationException($e->validator);
        }
        $phone = DB::table('users_phones')->where('user_phone_country' ,  $user_phone_country)->where('user_phone' ,  $user_phone)->first();
        if(!empty($phone))
        {
            $user = $this->user->find($phone->user_id);
            if(password_verify($password, $user->user_pwd))
            {
                $token = auth()->login($user);
                return $this->respondWithToken($token , false);
            }
        }else{
            return $this->response->errorUnauthorized(trans('auth.phone_failed'));
        }
    }

    public function signOut()
    {
        if(auth()->check())
        {
            auth()->logout();
        }
        return $this->response->noContent();
    }

    public function update(Request $request)
    {
        $fields = array();
        $user = auth()->user();
        $country_code = strtolower(strval($request->input('country_code')));
        $user_birthday = strval($request->input('user_birthday' , ''));
        $user_about = strval($request->input('user_about' , ''));
        $user_avatar = strval($request->input('user_avatar' , ''));
        $user_cover = strval($request->input('user_cover' , ''));
        $user_gender = $request->input('user_gender');
        $user_nick_name = mb_substr(strval($request->input('user_nick_name' , '')) , 0 , 64);
        $user_picture = (array)$request->input('user_picture' , array());
        $tag_slug = array_diff((array)$request->input('tag_slug' , array()),array(null , ''));
        $region_slug = array_diff((array)$request->input('region_slug' , array()),array(null , ''));
        $user_picture = \array_filter($user_picture , function($v , $k){
            return !empty($v);
        } , ARRAY_FILTER_USE_BOTH );
        ksort($user_picture);
        if($request->has('user_picture'))
        {
            $user_picture = array_slice($user_picture,0 , 8);
            $user_picture_json = \json_encode($user_picture);
            $fields['user_picture'] = $user_picture_json;
        }
        if(!empty($user_birthday))
        {
            $fields['user_birthday'] = $user_birthday;
        }
        if(!empty($country_code))
        {
            $fields['user_country_id'] = $country_code;
        }
        if(!empty($user_avatar))
        {
            $fields['user_avatar'] = $user_avatar;
        }
        if(!empty($user_about))
        {
            $fields['user_about'] = $user_about;
        }
        if(!empty($user_cover))
        {
            $fields['user_cover'] = $user_cover;
        }
        if($user_gender!==null)
        {
            $fields['user_gender'] = intval($user_gender);
        }
        if(!blank($user_nick_name))
        {
            $fields['user_nick_name'] = strval($user_nick_name);
        }
        $fields = array_filter($fields , function($value){
            return !blank($value);
        });
        \Validator::make($fields, $this->updateRules() , $this->updateMessages())->validate();
        if(!empty($fields))
        {
            $user = $this->user->update($user,$fields);
            event(new UserUpdatedEvent($user));
        }
        if($request->has('tag_slug'))
        {
            $this->user->attachTags($user , $tag_slug);
        }
        if($request->has('region_slug'))
        {
            $this->user->attachRegions($user , $region_slug);
        }
        $user->user_avatar = $user->user_avatar_link;
        $user->user_cover = $user->user_cover_link;
        $user->user_picture = $user->user_picture_link;
        return $user;
    }
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

    protected function credentials($request)
    {
        $user_fields = $request->only($this->username(), 'password');
        $credentials['password'] = $user_fields['password'];
        //$this->user->isDeletedUser($user_fields[$this->username()]);
        $supportFields = array($this->user->getDefaultEmailField() , $this->user->getDefaultNameField());
        foreach ($supportFields as $field) {
            if (empty($user_fields[$field])) {
                $credentials[$field] = $user_fields[$this->username()];
            }
        }
        return $credentials;
    }


    /**
     * @return mixed
     * 个人主页
     */
    public function me()
    {
        $user = auth()->user();
        return $this->response->array($user);
    }

    public function username()
    {
        return 'name';
    }


    public function handleSignIn(Request $request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        $code = strval($request->input('code' , ''));
        $phone = $user_phone_country.$user_phone;
        $validationField = array(
            'user_phone'=>$phone,
            'code'=> $code
        );
        $rule = [
            'user_phone' => [
                'bail',
                'string',
                'required',
                new UserPhone()
            ],
            'code' => [
                'bail',
                'string',
                'required',
                function ($attribute, $value, $fail) use ($phone){
                    $key = 'helloo:account:service:account-sign-in-sms-code:'.$phone;
                    $code = Redis::get($key);
                    Redis::del($key);
                    if($code!=$value)
                    {
//                        $fail('sms error');
                    }
                },
            ]
        ];
        \Validator::make($validationField, $rule)->validate();
        $phone = DB::table('users_phones')->where('user_phone_country' ,  $user_phone_country)->where('user_phone' ,  $user_phone)->first();
        if(!empty($phone))
        {
            $user = $this->user->find($phone->user_id);
            $token = auth()->login($user);
            return $this->respondWithToken($token , false);
        }
        $now = Carbon::now()->toDateTimeString();
        $user_fields = array(
            'user_crated_at'=>$now,
            'user_updated_at'=>$now,
            'user_uuid'=>Uuid::uuid1()
        );
        DB::beginTransaction();
        try{
            $userId = \DB::table('users')->insertGetId($user_fields);
            \DB::table('users_phones')->insert(array('user_id'=>$userId , 'user_phone'=>$user_phone , 'user_phone_country'=>$user_phone_country));
            \DB::commit();
        }catch (\Exception $e)
        {
            \DB::rollBack();
            \Log::error('sign_up_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
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

    public function randUsername(){
        return (new RandomStringGenerator())->generate(16);
    }



    public function accountExists($account , $type)
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
                        new UserPhoneUnique()
                    ],
                ];
            }else{
                $type = 'user_'.$type;
                $rule = [
                    $type => [
                        'bail',
                        'required',
                        'min:4',
                        'max:24'
                    ],
                ];
            }
            \Validator::make(array($type=>$account), $rule)->validate();
            $response = $response->noContent()->statusCode(200);
            if($type=='phone')
            {
                $existRule = [
                    "phone" => [
                        new UserPhoneUnique()
                    ],
                ];
                $validator = \Validator::make(array($type=>$account), $existRule);
                $response = $response->header('is_sign_up', intval($validator->fails()));
            }
            return $response->setStatusCode(200);
        }else{
            $response = $response->errorMethodNotAllowed();
        }
        return $response;
    }

    public function resetPwdByPhone(Request $request)
    {
        $response = $this->resetByPhone($request);
        if($response!==true)
        {
            return $this->response->errorNotFound(trans(strval($response)));
        }
        return $this->response->noContent();
    }

    private function forgetPwdByPhone($user_phone , $user_phone_country)
    {
        $rule = [
            'user_phone' => [
                'bail',
                'required',
                new UserPhone()
            ]
        ];
        $validationField = array('user_phone'=>$user_phone_country.$user_phone);
        \Validator::make($validationField, $rule)->validate();
        $phone = \DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
        if(!blank($phone))
        {
            $code = (new RandomStringGenerator('1234567890'))->generate(6);
            \DB::table('phone_password_resets')->where('phone_country' , $user_phone_country)->where('phone' , $user_phone)->delete();
            \DB::table('phone_password_resets')->insert(
                array(
                    'phone_country'=>$user_phone_country,
                    'phone'=>$user_phone,
                    'code'=>$code,
                    'created_at'=>date('Y-m-d H:i:s' , time()),
                )
            );
            $this->dispatch((new Sms($user_phone , $code , $user_phone_country))->onQueue('forget_pwd_sms'));
        }
        return $this->response->noContent();
    }



}
