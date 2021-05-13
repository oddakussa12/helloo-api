<?php

namespace App\Http\Controllers\V1;


use App\Models\Business\Shop;
use Carbon\Carbon;
use App\Traits\CachableUser;
use Illuminate\Http\Request;
use App\Resources\TagCollection;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use libphonenumber\PhoneNumberUtil;
use Illuminate\Support\Facades\Log;
use App\Custom\Agora\RtcTokenBuilder;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\UserTagRepository;

class UserController extends BaseController
{

    use CachableUser;

    /**
     * @var UserRepository
     */
    private $user;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    public function index(Request $request)
    {
        $phone = $request->input('phone' , '');
        $phoneCountry = $request->input('phoneCountry' , '');
        $username = strval($request->input('username' , ''));
        $keyword = strval($request->input('keyword' , ''));
        $userId = auth()->id();
        if(!blank($username))
        {
            $rules = array(
                'user_name' => [
                    'bail',
                    'required',
                    'string',
//                    'alpha_num',
                    'between:1,32'
                    ]);
            $validationField = array(
                'user_name' => $username
            );
            $len = strlen($username);
            $mbLen = mb_strlen($username);
            if(Validator::make($validationField, $rules)->fails()||$mbLen!==$len)
            {
                return $this->response->array(array('data'=>array()));
            }
            $users = $this->user->allWithBuilder()->where('user_activation' , 1)->where('user_name',$username)->select('user_id', 'user_avatar' , 'user_name' , 'user_nick_name', 'user_about' , 'user_gender', 'user_school', 'user_sl' , 'user_birthday')->limit(1)->get();
            $users = $users->filter(function($user) use ($userId){
                return  $user->user_id!=$userId;
            })->values();
            $userIds = $users->pluck('user_id')->toArray();
            $userSchools = $users->pluck('user_school')->toArray();
//            $schools = DB::table('schools')->whereIn('key' , $userSchools)->get();
            $friendIds = !blank($userIds)?DB::table('users_friends')->where('user_id' , $userId)->whereIn('friend_id' , $userIds)->get()->pluck('friend_id')->toArray():$userIds;
            $users->each(function($user , $index) use ($friendIds){
                $user->is_friend = in_array($user->user_id , $friendIds);
//                $user->user_school = $schools->where('key' , $user->user_school)->pluck('name')->first();
            });
            return UserCollection::collection($users);
        }elseif(!blank($phone)&&!blank($phoneCountry))
        {
            $userPhone = DB::table('users_phones')->where('user_phone_country' , $phoneCountry)->where('user_phone' , $phone)->first();
            if(blank($userPhone)||$userPhone->user_id==auth()->id())
            {
                return $this->response->array(array('data'=>array()));
            }
            $users = $this->user->allWithBuilder()->where('user_activation' , 1)->where('user_id' , $userPhone->user_id)->select('user_id', 'user_avatar' , 'user_name' , 'user_nick_name', 'user_about', 'user_gender', 'user_school', 'user_sl'  , 'user_birthday')->get();
            if(blank($users))
            {
                return $this->response->array(array('data'=>array()));
            }
            $userIds = $users->pluck('user_id')->toArray();
            $userSchools = $users->pluck('user_school')->toArray();
//            $schools = DB::table('schools')->whereIn('key' , $userSchools)->get();
            $friendIds = !blank($userIds)?DB::table('users_friends')->where('user_id' , $userId)->whereIn('friend_id' , $userIds)->get()->pluck('friend_id')->toArray():$userIds;
            $users = $users->filter(function($user) use ($friendIds){
                return  !in_array($user->user_id , $friendIds);
            })->values();
            $users->each(function($user , $index) use ($friendIds){
                $user->is_friend = in_array($user->user_id , $friendIds);
//                $user->user_school = $schools->where('key' , $user->user_school)->pluck('name')->first();
            });
            return UserCollection::collection($users);
        }elseif (!blank($keyword))
        {
            $keyword = mb_substr($keyword , 0 , 30);
            $len = strlen($keyword);
            $mbLen = mb_strlen($keyword);
            $keyword = escape_like($keyword);
            if($mbLen!==$len)
            {
                $users = $this->user->allWithBuilder()->where('user_activation' , 1)->where('user_nick_name', 'like', "%{$keyword}%")->orderByRaw("REPLACE(user_nick_name,'{$keyword}','')")->select('user_id', 'user_avatar' , 'user_name' , 'user_nick_name', 'user_about', 'user_gender' , 'user_sl' , 'user_birthday')->limit(20)->get();
            }else{
                $users = $this->user->allWithBuilder()->where('user_activation' , 1)->where(function ($query) use ($keyword) {
                    $query->where('user_nick_name', 'like', "%{$keyword}%")->orWhere('user_name', 'like', "%{$keyword}%");
                })->orderByRaw("REPLACE(user_nick_name,'{$keyword}','')")->orderByRaw("REPLACE(user_name,'{$keyword}','')")->select('user_id', 'user_avatar' , 'user_name' , 'user_nick_name', 'user_about', 'user_gender' , 'user_sl' , 'user_birthday')->limit(20)->get();
            }

            $users = $users->filter(function($user) use ($userId){
                return  $user->user_id!=$userId;
            })->values();
            $userIds = $users->pluck('user_id')->toArray();
            $friendIds = !blank($userIds)?DB::table('users_friends')->where('user_id' , $userId)->whereIn('friend_id' , $userIds)->get()->pluck('friend_id')->toArray():$userIds;
//            $users->each(function($user , $index) use ($friendIds){
//                $user->is_friend = in_array($user->user_id , $friendIds);
//            });
            $users = $users->filter(function($user) use ($friendIds){
                return  !in_array($user->user_id , $friendIds);
            })->values();
            return UserCollection::collection($users);
        }else{
            return $this->response->array(array('data'=>array()));
        }
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return UserCollection
     */
    public function show($id)
    {
        if($id==97623)
        {
            return $this->response->noContent();
        }
        if ($this->isBlocked($id)) {
            return $this->response->errorNotFound('Sorry, this account does not exist or is blocked!');
        }
        $user = $this->user->findByUserId($id);
        if(blank($user))
        {
            return $this->response->errorNotFound('Sorry, this account does not exist or is blocked!');
        }
        //个人隐私设置
        $mKey    = 'helloo:account:service:account-privacy:'.$id;
        $privacy = Redis::get($mKey);
        $privacy = !empty($privacy) ? json_decode($privacy, true) : ['friend'=>"1", 'video'=>"1",'photo'=>"1"];

        // 积分 排行
        $memKey = 'helloo:account:user-score-rank';
        $rank   = Redis::zrevrank($memKey , $id);
        $rank   = !empty($rank) ? $rank : Redis::zcard($memKey);

        $likeState = auth()->check()?!blank(DB::table('likes')->where('user_id' , auth()->id())->where('liked_id' , $id)->first()):false;
        $friend = auth()->check()?DB::table('users_friends')->where('user_id' , auth()->id())->where('friend_id' , $id)->first():null;
        $likedKey = 'helloo:account:service:account-liked-num';
        $user->put('likedCount' , intval(Redis::zscore($likedKey , $id)));
        $user->put('friendCount' , 0);
        $user->put('isFriend' , !blank($friend));
        $user->put('likeState' , $likeState);
        $user->put('privacy', $privacy);
        $user->put('rank', (int)$rank+1);
        $user->put('score', (int)Redis::zscore($memKey, $id));

        if(!empty($user->get('user_shop')))
        {
            $shop = Shop::where('id' , $user->get('user_shop'))->first();
            $user->put('shop' , new AnonymousCollection($shop));
        }
        return new UserCollection($user);
    }


    public function block($userId)
    {
        $this->user->blockUser($userId);
        return $this->response->created();
    }

    public function unblock($userId)
    {
        $this->user->unblockUser($userId);
        return $this->response->created();
    }

    public function randomVideo(Request $request)
    {
        $self = auth()->id();
        $random = $this->user->randomVideo($self);
        if($random['flag']==true)
        {
            $random['user'] = new UserCollection($this->user->findByUserId($random['userId']));
        }
        unset($random['userId']);
        return $this->response->array($random);
    }

    public function randomVoice(Request $request)
    {
        $self = auth()->id();
        $random = $this->user->randomVoice($self);
        if($random['flag']==true)
        {
            $random['user'] = new UserCollection($this->user->findByUserId($random['userId']));
        }
        unset($random['userId']);
        return $this->response->array($random);
    }

    public function removeVideo(Request $request)
    {
        $this->user->removeVideo();
        return $this->response->noContent();
    }

    public function removeVoice(Request $request)
    {
        $this->user->removeVoice();
        return $this->response->noContent();
    }


    public function randRyOnlineUser(Request $request)
    {
        $self = auth()->id();
        $userId = $this->user->randomIm($self);
        $user = $this->user->findByUserId($userId);
        if(blank($user))
        {
            return $this->response->errorNotFound('Failed to find friends, please try again');
        }
        return new UserCollection($user);
    }

    public function status(Request $request)
    {
        $userIds = (array)$request->all();
        $data = collect($userIds)->filter(function($userId , $key){
            return is_numeric($userId);
        })->transform(function ($userId , $key){
            return intval($userId);
        })->unique()->transform(function ($userId , $key){
            return array('userId'=>$userId , 'status'=>$this->user->isOnline($userId));
        })->toArray();
        return $this->response->array($data);
    }

    public function isRyOnline($id)
    {
        return $this->response->array(array(
            'status'=>$this->user->isOnline($id)
        ));
    }

    public function updateRyUserOnlineState(Request $request)
    {
        $response = $this->response->noContent();
        $users = $request->post();
        $this->user->updateUserOnlineState($users);
        return $response->setStatusCode(200);
    }


    public function planet()
    {
        $data = $this->user->planet();
        $userId = intval(auth()->id());
        $data = array_diff($data , [$userId]);
        $count = count($data);
        if($count<50)
        {
            $data = array_merge($data , range(63 , 200));
        }
        $users = $this->user->findByMany($data);
        $total = $this->user->onlineUsersCount();
        $users = UserCollection::collection($users)->additional(array(
            'total'=>intval($total*mt_rand(45 , 56))
        ));
        return $users;
    }

    public function tag($userId)
    {
        $userTags = app(UserTagRepository::class)->getByUserId($userId);
        $tagIds = $userTags->pluck('tag_id')->all();
        $tags = app(TagRepository::class)->findByMany($tagIds);
        return TagCollection::collection($tags);
    }

    public function like($userId)
    {
        $authId = auth()->id();
        $like = DB::table('likes')->where('user_id' , $authId)->where('liked_id' , $userId)->first();
        if(empty($like)&&$authId!=$userId)
        {
            $liked = DB::table('users')->where('user_id' , $userId)->first();
            if(!empty($liked))
            {
                DB::table('likes')->insert(
                    array('user_id'=>$authId , 'liked_id'=>$userId , 'created_at'=>Carbon::now()->toDateTimeString())
                );
                $count = intval(DB::table('likes')->where('liked_id' , $userId)->count());
                $likedKey = 'helloo:account:service:account-liked-num';
                Redis::zadd($likedKey , $count , $userId);
            }
        }
        return $this->response->created();
    }

    public function randomVideoV2(Request $request)
    {
        $self = auth()->id();
        $random = $this->user->randomVideoV2($self);
        if($random['flag']==true)
        {
            $random['user'] = new UserCollection($this->user->findByUserId($random['userId']));
        }
        unset($random['userId']);
        return $this->response->array($random);
    }

    public function randomVoiceV2(Request $request)
    {
        $self = auth()->id();
        $random = $this->user->randomVoiceV2($self);
        if($random['flag']==true)
        {
            $random['user'] = new UserCollection($this->user->findByUserId($random['userId']));
        }
        unset($random['userId']);
        return $this->response->array($random);
    }

    public function contactsV2(Request $request)
    {
        $userOddPhoneKey = "helloo:account:service:account-phone-{odd}-number";
        $userEvenPhoneKey = "helloo:account:service:account-phone-{even}-number";
        $userId = auth()->id();
        $keyPrefix = "helloo:account:service:account-{phone}-number:";
        $contacts = (array)$request->all();
        $phoneUtil = PhoneNumberUtil::getInstance();
        $contacts = collect($contacts)->map(function($userPhone , $key) use ($phoneUtil){
            $value = "+".trim($userPhone , "+");
            try{
                $numberProto = $phoneUtil->parse($value);
                $result = $phoneUtil->isValidNumber($numberProto);
                if($result===true)
                {
                    return array('phone_country'=>$numberProto->getCountryCode() , 'phone'=>$numberProto->getNationalNumber());
                }else{
                    return array('phone'=>$userPhone);
                }
            }catch (\Exception $e)
            {
                return array('phone'=>$userPhone);
            }
        })->filter(function($contact , $key){
            return isset($contact['phone_country']);
        })->values();
//        $contacts = collect($contacts)->filter(function($userPhone , $key){
//            return !blank($userPhone)&&isset($userPhone['phone_country'])&&isset($userPhone['phone'])&&is_numeric($userPhone['phone_country'])&&is_numeric($userPhone['phone']);
//        })->values();
        $contacts = $contacts->slice(0 , 200);
        $contacts = $contacts->map(function($userPhone , $key) use ($keyPrefix , $userOddPhoneKey , $userEvenPhoneKey){
            $phone = intval(ltrim($userPhone['phone'] , 0));
            $key = strval(ltrim(ltrim($userPhone['phone_country'] , "+") ,0)).'-'.strval(ltrim($userPhone['phone'] , 0));

            if($phone%2===0)
            {
                $userId = intval(Redis::zscore($userEvenPhoneKey , $key));
            }else{
                $userId = intval(Redis::zscore($userOddPhoneKey , $key));
            }
            $userPhone['user_id'] = $userId;
            return $userPhone;
        });
        $userIds = $contacts->pluck('user_id')->filter(function ($value, $key) {
            return intval($value) > 0;
        })->all();
        $users = $this->user->findByUserIds($userIds);
        if(blank($userIds))
        {
            $friendIds = $userIds;
        }else{
            $friendIds = !blank($userIds)?DB::table('users_friends')->where('user_id' , $userId)->whereIn('friend_id' , $userIds)->get()->pluck('friend_id')->toArray():$userIds;
        }
        $contacts = $contacts->map(function($contact , $key) use ($users , $friendIds){
            $user = $users->where('user_id' , $contact['user_id'])->first();
            if(!blank($user))
            {
                $user['status'] = $this->user->isOnline($contact['user_id']);
                $user['is_friend'] = in_array($contact['user_id'] , $friendIds);
            }
            return array(
                'phone_country'=>$contact['phone_country'],
                'phone'=>$contact['phone'],
                'user'=>$user,
            );
        });
        return UserCollection::collection($contacts);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function contacts(Request $request)
    {
        $userOddPhoneKey = "helloo:account:service:account-phone-{odd}-number";
        $userEvenPhoneKey = "helloo:account:service:account-phone-{even}-number";
        $userId = auth()->id();
        $keyPrefix = "helloo:account:service:account-{phone}-number:";
        $contacts = (array)$request->all();
        $contacts = collect($contacts)->filter(function($userPhone , $key){
            return !blank($userPhone)&&isset($userPhone['phone_country'])&&isset($userPhone['phone'])&&is_numeric($userPhone['phone_country'])&&is_numeric($userPhone['phone']);
        })->values();

        $contacts = $contacts->slice(0 , 200);
        $contacts = $contacts->map(function($userPhone , $key) use ($keyPrefix , $userOddPhoneKey , $userEvenPhoneKey){
            $phone = intval(ltrim($userPhone['phone'] , 0));
            $key = strval(ltrim(ltrim($userPhone['phone_country'] , "+") ,0)).'-'.strval(ltrim($userPhone['phone'] , 0));
            if($phone%2===0)
            {
                $userId = intval(Redis::zscore($userEvenPhoneKey , $key));
            }else{
                $userId = intval(Redis::zscore($userOddPhoneKey , $key));
            }
            $userPhone['user_id'] = $userId;
            return $userPhone;
        });
        $userIds = $contacts->pluck('user_id')->filter(function ($value, $key) {
            return intval($value) > 0;
        })->all();
        $users = $this->user->findByUserIds($userIds);
        if(blank($userIds))
        {
            $friendIds = $userIds;
        }else{
            $friendIds = !blank($userIds)?DB::table('users_friends')->where('user_id' , $userId)->whereIn('friend_id' , $userIds)->get()->pluck('friend_id')->toArray():$userIds;
        }
        $contacts = $contacts->map(function($contact , $key) use ($users , $friendIds){
            $user = $users->where('user_id' , $contact['user_id'])->first();
            if(!blank($user))
            {
                $user['status'] = $this->user->isOnline($contact['user_id']);
                $user['is_friend'] = in_array($contact['user_id'] , $friendIds);
            }
            return array(
                'phone_country'=>$contact['phone_country'],
                'phone'=>$contact['phone'],
                'user'=>$user,
            );
        });
        return UserCollection::collection($contacts);
    }

    public function gameTag(Request $request)
    {
        $tag = strval($request->input('tag' , ''));
        $color = strval($request->input('color' , '0'));
        Log::info('all' ,$request->all());
        if(!blank($tag)&&!blank($color))
        {
            $userId = auth()->id();
            $key = "helloo:account:service:tag:account:".$userId;
            $tags = DB::table('users_game_tags')->where('user_id' , $userId)->first();
            if(blank($tags))
            {
                $result = DB::table('users_game_tags')->insert(array(
                    'user_id'=>$userId,
                    'tag'=>$tag,
                    'color'=>$color,
                    'created_at'=>Carbon::now()->toDateTimeString(),
                ));
                if($result)
                {
                    Redis::del($key);
                    Redis::set($key , $tag.'|'.$color);
                    Redis::expire($key , 60*60*24);
                }
            }else{
                $count = DB::table('users_game_tags')->where('user_id' , $userId)->update(
                    array(
                        'tag'=>$tag,
                        'color'=>$color,
                        'created_at'=>Carbon::now()->toDateTimeString()
                    )
                );
                if($count>0)
                {
                    Redis::del($key);
                    Redis::set($key , $tag.'|'.$color);
                    Redis::expire($key , 60*60*24);
                }
            }
        }
        return $this->response->created();
    }

    public function recommendation()
    {
        $user = auth()->user();
        $school = $user->user_sl;
        $grade = $user->user_grade;
        $users = collect();
        if(!blank($school)&&$school!='Others')
        {
            $users = $this->user->allWithBuilder()->where('user_activation' , 1)->where('user_sl' , $school);
//            if(!blank($grade))
//            {
////                $users = $users->where('user_grade' , $grade)->inRandomOrder();
//            }
            $users = $users->inRandomOrder()->select(array(
                'user_id',
                'user_name',
                'user_nick_name',
                'user_avatar',
            ))->limit(8)->get();
            if(blank($users))
            {
                $users = $this->user->allWithBuilder()->where('user_activation' , 1)->inRandomOrder()->select(array(
                    'user_id',
                    'user_name',
                    'user_nick_name',
                    'user_avatar',
                ))->limit(6)->get();
            }
        }else{
            $users = $this->user->allWithBuilder()->where('user_activation' , 1)->inRandomOrder()->select(array(
                'user_id',
                'user_name',
                'user_nick_name',
                'user_avatar',
            ))->limit(6)->get();
        }
        $userIds = $users->pluck('user_id')->toArray();
        $friendIds = !blank($userIds)?DB::table('users_friends')->where('user_id' , $user->user_id)->whereIn('friend_id' , $userIds)->get()->pluck('friend_id')->toArray():$userIds;
        array_push($friendIds , $user->user_id);
        $users = $users->reject(function ($u) use ($friendIds){
            return in_array($u->user_id , $friendIds);
        })->splice(0 , 3);
        return UserCollection::collection($users);
    }

    public function agoraToken()
    {
        $targetId = intval(request()->input('target_id' , 0));
        if($targetId<=0)
        {
            abort(400);
        }
        $appID = config('agora.app_id');
        $appCertificate = config('agora.app_certificate');;
        $selfUidStr = strval(auth()->id());
        $targetUidStr = strval($targetId);
        $channelName = strval(app('snowflake')->id()).'-'.$selfUidStr.'-'.strval(millisecond()).'-'.$targetUidStr;
//        $uid = 2882341273;
        $role = RtcTokenBuilder::RoleAttendee;
        $expireTimeInSeconds = 3600;
        $currentTimestamp = (new \DateTime("now", new \DateTimeZone('UTC')))->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

//        $token = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);
//        echo 'Token with int uid: ' . $token . PHP_EOL;

        $selfToken = RtcTokenBuilder::buildTokenWithUserAccount($appID, $appCertificate, $channelName, $selfUidStr, $role, $privilegeExpiredTs);
        $targetToken = RtcTokenBuilder::buildTokenWithUserAccount($appID, $appCertificate, $channelName, $targetUidStr, $role, $privilegeExpiredTs);
        return $this->response->array(array('data'=>array(
            'channel'=>$channelName,
            'selfToken'=>$selfToken,
            'targetToken'=>$targetToken,
        )));
    }
}
