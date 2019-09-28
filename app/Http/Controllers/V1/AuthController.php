<?php

namespace App\Http\Controllers\V1;

use Socialite;
use Ramsey\Uuid\Uuid;
use App\Events\SignupEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\LoginUserRequest;
use Fenos\Notifynder\Facades\Notifynder;
use App\Repositories\Contracts\UserRepository;
use Dingo\Api\Exception\StoreResourceFailedException;



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
        $index = array_search($addresses->iso_code , config('countries'));
        if($index===false)
        {
            $index = 208;
        }
        $user_fields['user_country_id'] = ($index+1);
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

    public function updateUserInfo(Request $request)
    {
        $user = auth()->user();
        $user = $this->user->update($user,$request->all());
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
    

    public function me()
    {
        return $this->response->array(auth()->user());
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
        dd(123);
        return Socialite::driver('google')->redirect();
    }

    /**
     * 从Google获取用户信息.
     *
     * @return Response
     */
    public function handleProviderCallback(Request $request)
    {
        $oauthType = $request['oauthType'];
        $user_fields['user_google'] = $request['user_google'];
        $user_info  = $this->user -> findOauth($oauthType,$user_fields['user_google']);
        //验证当前用户是否登录过
        if($user_info){
            $token = auth()->login($user_info);
            return $this->respondWithToken($token);
        }else{
            $user_fields= array();
            $user_fields['user_'.$request['oauthType']] = $request['user_authid'];
            $user_fields['user_name'] = $request['name'];
            $user_fields['user_email'] = $request['email'];
            $user_fields[$this->user->getDefaultPasswordField()] =$request['user_authid'];
            // $user_fields['user_first_name'] = $request['user_first_name'];
            // $user_fields['user_last_name'] = $request['user_last_name'];
            $user_fields['user_avatar'] = $request['user_avatar'];
            $user_fields['user_language'] = $request['user_language'];
            $user_fields['user_ip_address'] = getRequestIpAddress();
            $user_fields['user_uuid'] = Uuid::uuid1();
            $addresses = geoip($user_fields['user_ip_address']);
            dd($user_fields);
            $index = array_search($addresses->iso_code , config('countries'));
            if($index===false)
            {
                $index = 208;
            }
            $user_fields['user_country_id'] = $index;
            $user = $this->user->store($user_fields);
            if ($user) {
                event(new SignupEvent($user , $addresses));
                $token = auth()->login($user);
                return $this->respondWithToken($token);
            }
            throw new StoreResourceFailedException('sign up failure');
        }
            // $user = Socialite::driver('google')->stateless()->user();
            // dd($user->user);
    }




}
