<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Sms;
use App\Jobs\Device;
use Ramsey\Uuid\Uuid;
use App\Rules\UserPhone;
use App\Events\SignupEvent;
use Illuminate\Http\Request;
use App\Traits\CachableUser;
use App\Rules\UserPhoneUnique;
use App\Events\UserUpdatedEvent;
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
    use CachableUser,ResetsPasswords, SendsPasswordResetEmails {
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
        $request_fields = $request->only(['name' , 'email' , 'password']);
        $referer = $request->input('referer' , 'web');
        $user_fields[$this->user->getDefaultNameField()] = $request_fields['name'];
        $user_fields[$this->user->getDefaultEmailField()] = $request_fields['email'];
        $user_fields[$this->user->getDefaultPasswordField()] = $request_fields['password'];
        $user_fields['user_ip_address'] = getRequestIpAddress();
        $user_fields['user_uuid'] = Uuid::uuid1();
        $user_fields['user_src'] = $referer;
        $addresses = geoip($user_fields['user_ip_address']);
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
        return $this->response->errorUnauthorized(trans('auth.failed'));
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
        $user = auth()->user();
        $country_code = $request->input('country_code');
        $user_age = $request->input('user_age');
        $user_about = $request->input('user_about');
        $user_avatar = $request->input('user_avatar');
        $user_cover = $request->input('user_cover');
        $user_gender = $request->input('user_gender');
        $user_nick_name = mb_substr(strval($request->input('user_nick_name' , '')) , 0 , 64);
        $user_picture = (array)$request->input('user_picture' , array());
        $tag_slug = array_diff($request->input('tag_slug' , array()),array(null , ''));
        $region_slug = array_diff($request->input('region_slug' , array()),array(null , ''));
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
        if(!empty($country_code))
        {
            $fields['user_country_id'] = $country_code;
        }
        if($user_age!==null)
        {
            $fields['user_age'] = $user_age;
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
            $deviceFields = request()->only(['vendorUUID' , 'deviceToken' , 'registrationId' , 'deviceLanguage']);
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
        $user->userTags = UserTagCollection::collection($user->tags);
        $user->userRegions = UserRegionCollection::collection($user->regions);
        $user->user_avatar = $user->user_avatar_link;
        $user->user_cover = $user->user_cover_link;
        $user->user_picture = $user->user_picture_link;
        unset($user->tags);
        unset($user->regions);
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
        $user_phone = $request->input('user_phone' , "");
        $user_phone_country = $request->input('user_phone_country' , "+86");
        $password = $request->input('password');
        $validationField = array(
            'user_nick_name'=>$user_nick_name,
            'user_phone'=>$user_phone_country.$user_phone,
            'password'=>$password,
        );
        $rule = [
            'user_nick_name' => [
                'bail',
                'required',
                'string'
            ],
            'user_phone' => [
                'bail',
                'required',
                new UserPhone(),
                new UserPhoneUnique()
            ],
            'password' => [
                'bail',
                'required',
                'string'
            ],
        ];
        \Validator::make($validationField, $rule)->validate();
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
            $user_fields['user_country_id'] = $request->input('country_code');
        }else{
            $user_fields['user_country_id'] = $addresses->iso_code;
        }
        \DB::beginTransaction();
        try{
            $userId = \DB::table('users')->insertGetId($user_fields);
            \DB::table('users_phones')->insert(array('user_id'=>$userId , 'user_phone'=>$user_phone , 'user_phone_country'=>$user_phone_country));
            \DB::commit();
        }catch (\Exception $e)
        {
            \DB::rollBack();
            throw new StoreResourceFailedException('sign up failed');
        }
        $user = $this->user->find($userId);
        event(new SignupEvent($user , $addresses));
        $token = auth()->login($user);
        return $this->respondWithToken($token , false);
    }
    public function handleSignIn(Request $request)
    {
        $user_phone = $request->input('user_phone' , "");
        $user_phone_country = $request->input('user_phone_country' , "86");
        $password = $request->input('password' , '');
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
        if(empty($phone))
        {
            return $this->response->errorUnauthorized(trans('auth.failed'));
        }
        $user = $this->user->find($phone->user_id);
        if(password_verify($password, $user->user_pwd))
        {
            $token = auth()->login($user);
            return $this->respondWithToken($token , false);
        }
        return $this->response->errorUnauthorized(trans('auth.failed'));

    }

    public function randUsername(){
        return (new RandomStringGenerator())->generate(16);
    }

    public function forgetPwd(ForgetPasswordRequest $request)
    {
        if($request->has('user_phone')&&$request->has('user_phone_country'))
        {
            $user_phone = $request->input('user_phone' , '');
            $user_phone_country = $request->input('user_phone_country' , "+86");
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
        if(in_array($type , array('name' , 'email' , 'phone')))
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
                        'regex:/^[\p{Thai}\p{Latin}\p{Hangul}\p{Han}\p{Hiragana}\p{Katakana}\p{Cyrillic}0-9a-zA-Z-_]+$/u',
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
            }else{
                $type = 'user_'.$type;
                $rule = [
                    $type => [
                        'bail',
                        'required',
                        new UserPhone(),
                        new UserPhoneUnique()
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
        if(blank($phone))
        {
            return $this->response->errorNotFound('The user corresponding to the mobile phone number could not be found.');
        }
        $code = mt_rand(111111 , 999999);
        $selectSql = <<<DOC
delete from f_phone_password_resets where phone_country = '{$user_phone_country}' and phone = '{$user_phone}';
DOC;
        \DB::statement($selectSql);
        \DB::table('phone_password_resets')->insert(
            array(
                'phone_country'=>$user_phone_country,
                'phone'=>$user_phone,
                'code'=>$code,
                'created_at'=>date('Y-m-d H:i:s' , time()),
            )
        );
        $this->dispatch(new Sms($user_phone , $code , $user_phone_country));
        return $this->response->noContent();
    }

}
