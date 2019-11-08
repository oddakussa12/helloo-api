<?php

namespace App\Http\Controllers\V1;

use Socialite;
use Ramsey\Uuid\Uuid;
use App\Models\PostComment;
use App\Events\SignupEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\LoginUserRequest;
use Fenos\Notifynder\Facades\Notifynder;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;
use Dingo\Api\Exception\StoreResourceFailedException;
use Faker\Factory;
use Illuminate\Support\Facades\Mail;

class AuthController extends BaseController
{

    protected $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function signUp(StoreUserRequest $request)
    {
        $request_fields = $request->only(['name' , 'email' , 'password']);
        $user_fields[$this->user->getDefaultNameField()] = $request_fields['name'];
        $user_fields[$this->user->getDefaultEmailField()] = $request_fields['email'];
        $user_fields[$this->user->getDefaultPasswordField()] = $request_fields['password'];
        $user_fields['user_ip_address'] = getRequestIpAddress();
        $user_fields['user_uuid'] = Uuid::uuid1();
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
        $user_avatar = $request->input('user_avatar');
        if(!empty($country_code))
        {
            $fields['user_country_id'] = $country_code;
        }
        if(!empty($user_avatar))
        {
            $fields['user_avatar'] = $user_avatar;
        }
        if(!empty($fields))
        {
            $user = $this->user->update($user,$fields);
        }
        return $user;
    }
    protected function respondWithToken($token)
    {
        $user = auth()->user();
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user'=>array(
                'user_id'=>$user->user_id,
                'user_name'=>$user->user_name,
                'user_avatar'=>$user->user_avatar,
                'user_email'=>$user->user_email,
                'user_country'=>$user->user_country,
            )
        ]);
    }

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


    public function me(Request $request)
    {
        $user = auth()->user();
        $likeCount = $user->likes()->where('likable_type' , PostComment::class)->with('likable')->count();
        $postCommentCount = app(PostCommentRepository::class)->getCountByUserId($request , $user->user_id);
        $postCount = app(PostRepository::class)->getCountByUserId($request , $user->user_id);
        $user_followmecount = auth()->user()->followers()->count();
        $user_myfollowcount = auth()->user()->followings()->count();
        $user->postCommentCount = $postCommentCount;
        $user->postCount = $postCount;
        $user->user_followmecount = $user_followmecount;
        $user->user_myfollowcount = $user_myfollowcount;
        $user->likeCount = $likeCount;
        $user->country = $user->user_country;
        return $this->response->array($user);
    }

    public function username()
    {
        return 'name';
    }

    public function test()
    {
        $disk = Storage::disk('idwebother');
        $token = $disk->getUploadToken();
        dd($token);
        Notifynder::category('user.following')
            ->from(54)
            ->to(2)
            ->extra(['message' => 'Hey John, I\'m Doe.']) // define additional data
            ->extra(['action' => 'invitation'], false) // extend additional data
            ->url('http://laravelacademy.org/notifications')
            ->send();
        $userNotified = $this->user->find(2);
        dd($userNotified->getNotificationsNotRead()->toArray());
    }
    /**
     * 将用户重定向到Google认证页面
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback(Request $request)
    {
        $oauthType = $request->input('oauthtype');
        $user_authid = $request->input('user_authid');
        $user_info  = $this->user -> findOauth($oauthType,$user_authid);
        $user_emailauth = $this->user->findByWhere(['user_email'=>$request->input('email')]);
        //验证当前用户是否登录过
        if($user_info){
            $token = auth()->login($user_info);
            return $this->respondWithToken($token);
        }else if($user_emailauth){
            $token = auth()->login($user_emailauth);
            return $this->respondWithToken($token);
        }else{
            //验证用户名和邮箱
            $user_name = $request->input('name');
            $user_email = $request->input('email');
            $user_avatar = $request->input('user_avatar');
            $user_language = $request->input('user_language');
            $user_nameauth = $this->user->findByWhere(['user_name'=>$user_name]);
            if($user_nameauth){
                throw new StoreResourceFailedException('sign up failure');
            }
            $user_fields= array();
            $user_fields['user_'.$oauthType] = $user_authid;
            $user_fields['user_name'] = $user_name;
            $user_fields['user_email'] = $user_email;
            $user_fields[$this->user->getDefaultPasswordField()] =$user_authid;
            // $user_fields['user_first_name'] = $request['user_first_name'];
            // $user_fields['user_last_name'] = $request['user_last_name'];
            $user_fields['user_avatar'] = $user_avatar;
            $user_fields['user_language'] = $user_language;
            $user_fields['user_ip_address'] = getRequestIpAddress();
            $user_fields['user_uuid'] = Uuid::uuid1();
            $addresses = geoip($user_fields['user_ip_address']);
            $user_fields['user_country_id'] = $addresses->iso_code;
            $user = $this->user->store($user_fields);
            if ($user) {
                event(new SignupEvent($user , $addresses));
                $token = auth()->login($user);
                return $this->respondWithToken($token);
            }
            throw new StoreResourceFailedException('sign up failure');
        }
    }

    public function guestSignUp(Request $request)
    {
        $request_fields = $request->only(['country' , 'country_code']);
        $user_name = $this->randUsername($request_fields['country']);
        $request_fields['name'] = $user_name;
        $user_fields[$this->user->getDefaultNameField()] = $request_fields['name'];
        $user_fields[$this->user->getDefaultEmailField()] = $request_fields['name'].'@yooul.com';
        $user_fields[$this->user->getDefaultPasswordField()] = $request_fields['name'].'mantou';
        $user_fields['user_ip_address'] = getRequestIpAddress();
        $user_fields['user_uuid'] = Uuid::uuid1();
        $user_fields['user_is_guest'] = 1;
        $addresses = geoip($user_fields['user_ip_address']);
        if($request->has('country_code'))
        {
            $user_fields['user_country_id'] = $request->input('country_code');
        }else{
            $user_fields['user_country_id'] = $addresses->iso_code;
        }
        $user = $this->user->store($user_fields);
        if ($user) {
            // event(new SignupEvent($user , $addresses));
            $token = auth()->login($user);
            return $this->respondWithToken($token);
        }
        throw new StoreResourceFailedException('sign up failed');
    }

    public function randUsername($country){
        $return_string = '';
        $tmpstr = substr(md5(microtime(true)), 0, 6);
        $num = mt_rand(2,9);

        $randusername = $country.$num.$tmpstr;

        return $randusername;

    }


}
