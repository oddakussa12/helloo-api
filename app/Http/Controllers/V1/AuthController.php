<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Sms;
use App\Jobs\Device;
use App\Models\BlackUser;
use Ramsey\Uuid\Uuid;
use App\Rules\UserPhone;
use App\Events\SignupEvent;
use Illuminate\Http\Request;
use App\Traits\CachableUser;
use App\Rules\UserPhoneUnique;
use App\Events\UserUpdatedEvent;
use App\Foundation\Auth\User\Update;
use App\Resources\UserTagCollection;
use App\Rules\UserNameAndEmailUnique;
use App\Resources\UserRegionCollection;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\LoginUserRequest;
use Illuminate\Support\Facades\Password;
use App\Custom\Uuid\RandomStringGenerator;
use App\Http\Requests\ForgetPasswordRequest;
use App\Repositories\Contracts\UserRepository;
use App\Foundation\Auth\Passwords\ResetsPasswords;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class AuthController extends BaseController
{
    use Update,CachableUser,ResetsPasswords, SendsPasswordResetEmails {
        ResetsPasswords::broker insteadof SendsPasswordResetEmails;
    }

    protected $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUserRequest $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function signUp(StoreUserRequest $request)
    {
        $request_fields = $request->only(['name' , 'email' , 'password' , 'user_nick_name']);
        $referer = $request->input('referer' , 'web');
        $user_fields[$this->user->getDefaultNameField()] = strval(!empty($request_fields['name'])?$request_fields['name']:$this->randUsername());
        $user_fields[$this->user->getDefaultEmailField()] = strval($request_fields['email']);
        $user_fields[$this->user->getDefaultPasswordField()] = strval($request_fields['password']);
        $user_fields['user_nick_name'] = strval(!empty($request_fields['user_nick_name'])?$request_fields['user_nick_name']:'');
        $user_fields['user_ip_address'] = getRequestIpAddress();
        $user_fields['user_uuid'] = Uuid::uuid1();
        $user_fields['user_src'] = $referer;
        $addresses = geoip($user_fields['user_ip_address']);
        $version = $request->header('YooulVersion' , 0);
        if(version_compare($version,'1.6.2','>='))
        {
            $user_fields['user_nick_name'] = empty($user_fields['user_nick_name'])?$user_fields[$this->user->getDefaultNameField()]:$user_fields['user_nick_name'];
            $user_fields[$this->user->getDefaultNameField()] = $this->randUsername();
        }
        if($request->has('country_code'))
        {
            $user_fields['user_country_id'] = $request->input('country_code');
        }else{
            $user_fields['user_country_id'] = $addresses->iso_code;
        }
        $user = $this->user->store($user_fields);
        if ($user) {
            event(new SignupEvent($user , $addresses));
            $token = auth()->login($user);
            return $this->respondWithToken($token);
        }
        throw new StoreResourceFailedException('sign up failed');
    }




    public function signIn(LoginUserRequest $request)
    {
        $credentials = $this->credentials($request);
        if ($token = auth()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }
        $error = filter_var($credentials['user_email'] , FILTER_VALIDATE_EMAIL)===false?trans('auth.failed'):trans('auth.email_failed');
        return $this->response->errorUnauthorized($error);
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
            $user = auth()->user();
            $rank = $this->userScoreRank($user->user_id);
            $user = array(
                    'user_id'=>$user->user_id,
                    'user_name'=>$user->user_name,
                    'user_avatar'=>$user->user_avatar_link,
                    'user_country'=>$user->user_country,
                    'user_level'=>$user->user_level,
                    'user_gender'=>$user->user_gender,
                    'yesterdayScore' => null,
                    'yesterdayRank' => null,
                    'userRank' => $rank
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


    public function me(Request $request)
    {
        $user = auth()->user();
//        $likeCount = $user->likes()->where('likable_type' , PostComment::class)->with('likable')->join('posts_comments' , function($join){
//            $join->on('common_likes.likable_id' , 'posts_comments.comment_id');
//        })->whereNull('posts_comments.comment_deleted_at')->count();
        $likeCount = $this->userPostCommentLike($user->user_id);
//        $postCommentCount = app(PostCommentRepository::class)->getCountByUserId($user->user_id);
        $postCommentCount = $this->userPostCommentCount($user->user_id);
//        $yesterday_score_rank = app(UserRepository::class)->getUserYesterdayRankByUserId($user->user_id);
//        $postCount = app(PostRepository::class)->getCountByUserId($user->user_id);
        $postCount = $this->userPostCount($user->user_id);
//        $rank = app(UserRepository::class)->getUserRankByUserId($user->user_id);
        $rank = $this->userScoreRank($user->user_id);
//        $userFollowMe = auth()->user()->followers()->count();
//        $userMyFollow = auth()->user()->followings()->count();
        $userFollowMe = $this->userFollowMeCount($user->user_id);
        $userMyFollow = $this->userMyFollowCount($user->user_id);
        $user->postCommentCount = $postCommentCount;
        $user->postCount = $postCount;
        $user->userFollowMe = $userFollowMe;
        $user->userMyFollow = $userMyFollow;
        $user->likeCount = $likeCount;
        $user->country = $user->user_country;
        $user->yesterdayScore = null;
        $user->yesterdayRank = null;
        $user->userRank = $rank;
        $user->userTags = UserTagCollection::collection($user->user_tags);
        $user->userRegions = UserRegionCollection::collection($user->user_regions);
        $user->user_avatar = $user->user_avatar_link;
        $user->user_cover = $user->user_cover_link;
        $user->user_picture = $user->user_picture_link;
        $phone = $this->getUser($user->user_id , array('user_phone_country' , 'user_phone'));
        $user->user_phone_country = empty($phone['user_phone_country'])?'':$phone['user_phone_country'];
        $user->user_phone = empty($phone['user_phone'])?'':$phone['user_phone'];
        $user->userNameIsCanUpdate = $this->isCanUpdateName($user->user_id);
        unset($user->user_tags);
        unset($user->user_regions);
        unset($user->user_country);
        return $this->response->array($user);
    }

    public function username()
    {
        return 'name';
    }

    public function handleSignUp(Request $request)
    {
        $user_nick_name = $request->input('user_nick_name');
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        if($request->has('user_phone_country：'))
        {
            $user_phone_country = ltrim(strval($request->input('user_phone_country：' , "86")) , "+");
        }else{
            $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        }
        $password = $request->input('password');
        $validationField = array(
            'nick_name'=>$user_nick_name,
            'phone'=>$user_phone_country.$user_phone,
            'password'=>$password,
        );
        $rule = [
            'nick_name' => [
                'bail',
                'required',
                'string',
                'min:4',
                'max:13',
            ],
            'phone' => [
                'bail',
                'required',
                new UserPhone(),
                new UserPhoneUnique()
            ],
            'password' => [
                'bail',
                'required',
                'string',
                'min:6',
                'max:16',
            ],
        ];
        try{
            \Validator::make($validationField, $rule)->validate();
        }catch (\Illuminate\Validation\ValidationException $e)
        {
            \Log::error($request->all());
            throw new \Illuminate\Validation\ValidationException($e->validator);
        }
        $dateTime = date("Y-m-d H:i:s");
        $referer = $request->input('referer' , 'web');
        $user_fields[$this->user->getDefaultNameField()] = $this->randUsername();
        $user_fields[$this->user->getDefaultPasswordField()] = bcrypt($password);
        $user_fields['user_ip_address'] = getRequestIpAddress();
        $user_fields['user_uuid'] = Uuid::uuid1();
        $user_fields['user_src'] = $referer;
        $user_fields['user_nick_name'] = $user_nick_name;
        $user_fields['user_created_at'] = $dateTime;
        $user_fields['user_updated_at'] = $dateTime;
        $addresses = geoip($user_fields['user_ip_address']);
        if($request->has('country_code'))
        {
            $user_fields['user_country_id'] = getUserCountryId($request->input('country_code'));
        }else{
            $user_fields['user_country_id'] = getUserCountryId($addresses->iso_code);
        }
        \DB::beginTransaction();
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
        event(new SignupEvent($user , $addresses , array(
            'user_phone'=>$user_phone,
            'user_phone_country'=>$user_phone_country,
        )));
        $token = auth()->login($user);
        return $this->respondWithToken($token , false);
    }
    public function handleSignIn(Request $request)
    {
        $user_phone = ltrim(ltrim(strval($request->input('user_phone' , "")) , "+") , "0");
        if($request->has('user_phone_country：'))
        {
            $user_phone_country = ltrim(strval($request->input('user_phone_country：' , "86")) , "+");
        }else{
            $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
        }
        $password = strval($request->input('password' , ''));
        $validationField = array(
            'user_phone'=>$user_phone_country.$user_phone,
            'password'=>$password,
        );
        $rule = [
            'user_phone' => [
                'bail',
                'required',
                new UserPhone()
            ],
            'password' => [
                'bail',
                'required',
                'string'
            ],
        ];
        \Validator::make($validationField, $rule)->validate();
        $phone = \DB::table('users_phones')->where('user_phone_country', $user_phone_country)->where('user_phone', $user_phone)->first();
        if(blank($phone))
        {
            return $this->response->errorUnauthorized(trans('auth.phone_failed'));
        }
        $user = $this->user->find($phone->user_id);
        if(password_verify($password, $user->user_pwd))
        {
            //todo  是否被封号
            $isBlack = $this->user->isBlackUser($user->user_id);
            if (!empty($isBlack)) {
                return $this->response->errorUnauthorized('该帐号涉嫌违规已被封禁');
            } else {
                $token = auth()->login($user);
                return $this->respondWithToken($token, false);
            }
        }
        return $this->response->errorUnauthorized(trans('auth.phone_failed'));

    }

    public function randUsername(){
        return (new RandomStringGenerator())->generate(16);
    }

    public function forgetPwd(ForgetPasswordRequest $request)
    {
        if($request->has('user_phone')&&$request->has('user_phone_country'))
        {
            $user_phone = ltrim(ltrim(strval($request->input('user_phone' , '')) , "+") , "0");
            $user_phone_country = ltrim(strval($request->input('user_phone_country' , "86")) , "+");
            $this->forgetPwdByPhone($user_phone , $user_phone_country);
            return $this->response->noContent();
        }
        $credentials = array('user_email'=>$request->input('email'));
        $referer = $request->input('referer' , '');
        $referer = empty($referer)?$request->server('HTTP_REFERER'):$referer;
        $referer = domain($referer);
        if(!in_array($referer , array_values(config('common.front_domain'))))
        {
            $referer = config('common.front_domain.h5');
        }
        $credentials['referer'] = $referer;

        $response = $this->broker()->sendResetLink($credentials);
        if($response==Password::RESET_LINK_SENT)
        {
            return $this->response->noContent();
        }
        return $this->response->errorNotFound(trans('passwords.user'));
    }

    public function resetPwd(Request $request)
    {
        $response = $this->reset($request);
        if($response!==true)
        {
            switch ($response)
            {
                case Password::INVALID_USER :
                    return $this->response->errorNotFound(trans('passwords.user'));
                    break;
                case Password::INVALID_PASSWORD :
                    return $this->response->errorNotFound(trans('passwords.password'));
                    break;
                case Password::INVALID_TOKEN :
                    return $this->response->errorNotFound(trans('passwords.token'));
                    break;
                case 'passwords.code' :
                    return $this->response->errorNotFound(trans('passwords.code'));
                    break;
                default :
                    return $this->response->errorNotFound(__('Service Unavailable'));
                    break;
            }
        }
        return $this->response->noContent();
    }

    public function accountExists($account , $type)
    {
        $response = $this->response;
        if(in_array($type , array('name' , 'email' , 'phone' , 'nick_name')))
        {
            $type = strtolower($type);
            if($type=='name')
            {
                $rule = [
                    $type => [
                        'required',
                        function ($attribute, $value, $fail) {
                            if (emoji_test($value)) {
                                $fail(trans('validation.regex'));
                            }
                        },
                        'regex:/^[0-9a-zA-Z]+$/u',
                        'min:4',
                        'max:32',
//                        'unique:users,user_'.$type
                        new UserNameAndEmailUnique()
                    ],
                ];
            }else if($type=='email'){
                $rule = [
                    $type => [
                        'required',
                        'email',
//                        'unique:users,user_'.$type
                        new UserNameAndEmailUnique()
                    ],
                ];
            }else if($type=='phone'){
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
                        'max:13'
                    ],
                ];
            }
            \Validator::make(array($type=>$account), $rule)->validate();
            $response = $response->noContent()->statusCode(200);
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
    public function sendUpdateEmailCode(Request $request)
    {
        $this->sendEmailCode($request);
        return $this->response->noContent();
    }

    public function sendUpdatePhoneCode(Request $request)
    {
        $this->sendPhoneCode($request);
        return $this->response->noContent();
    }

    public function updateUserName(Request $request)
    {
        $this->updateName($request);
        return $this->response->noContent();
    }

    public function updateUserPhone(Request $request)
    {
        $this->updatePhone($request);
        return $this->response->noContent();
    }

    public function updateUserEmail(Request $request)
    {
        $this->updateEmail($request);
        return $this->response->noContent();
    }

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
        \Validator::make($validationField, $rule)->validate();
        return $this->response->noContent();
    }

    public function updateAuth(Request $request)
    {
        $this->fillAuth($request);
        return $this->response->noContent();
    }



}
