<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Models\Es;
use App\Models\User;
use App\Events\Follow;
use App\Events\UnFollow;
use App\Models\UserEmoji;
use App\Models\UserFriend;
use App\Models\UserVisitLog;
use App\Resources\UserSearchCollection;
use App\Traits\CachableUser;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Resources\FollowCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Contracts\UserRepository;

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
    private $searchUser;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
        $this->searchUser = config('scout.elasticsearch.user');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $name = $request->input('name' , '');
        $users = collect(array());
        $rule = [
            'name' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (emoji_test($value)) {
                        $fail(trans('validation.regex'));
                    }
                },
                'regex:/^[\p{Thai}\p{Latin}\p{Hangul}\p{Han}\p{Hiragana}\p{Katakana}\p{Cyrillic}0-9a-zA-Z-_]+$/u'
            ],
        ];
        $validator = \Validator::make(array('name'=>$name), $rule);
        if (!$validator->fails()) {

            $params['keyword'] = mb_str_limit(trim($name), 20, null);

            $user  = (new Es($this->searchUser, ['limit'=>10]))->likeQuery($params, true);
            $user  = $user->appends($params);
            $users = UserSearchCollection::collection($user);

            $users = $users->filter(function ($user) {
                return !$this->isBlocked($user['user_id']);
            })->values();


            /*$nameUsers = UserCollection::collection($this->user->findByLikeName($name));
            $nicknameUsers = UserCollection::collection($this->user->findByLikeNickName($name));
            $users = $nameUsers->merge($nicknameUsers)->unique('user_id');
            $users = $users->filter(function ($user, $key) {
                $userId = $user['user_id'];
                return !$this->isBlocked($userId);
            })->values();
            return $this->response->array(array(
                'data'=>$users
            ));*/
        }
        return $this->response->array(['data' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return UserCollection
     */
    public function show($id)
    {
        if ($this->isBlocked($id)) {
            return $this->response->errorNotFound();
        }

        $user        = $this->user->findOrFail($id);
        $user        = $this->user->virtualViewCount($user);
        $followerIds = $this->user->userFollow([$id]);
        $user->user_follow_state = !empty($followerIds);
        return new UserCollection($user);
    }


    /**
     * @param $id
     * @return array
     * 我得主页 访客统计
     */
    public function viewPage(int $id)
    {
        $user  = $this->user->findOrFail($id);
        $total = Redis::hget(config('redis-key.user.user_visit'), $id);

        $total    = $user->virtual_view_count+$total;
        $today    = date('Y-m-d');
        $count    = UserVisitLog::where('friend_id', $id)->where('created_at', '>=', $today)->count();

        $data     = UserVisitLog::where('friend_id', $id)->where('created_at', '>=', $today)
            ->orderBy('created_at', 'desc')->groupBy('user_id')->limit(10)->get();
        $userIds  = $data->pluck('user_id')->toArray();
        $friends  = UserFriend::where('user_id', $id)->whereIn('friend_id', $userIds)->pluck('friend_id')->toArray();
        $userList = User::whereIn('user_id', $userIds)->get();

        $userList->each(function ($user) use ($data, $friends) {
            $user->is_friend = in_array($user->user_id, $friends);
            $data->each(function ($item) use($user) {
                if ($item->user_id==$user->user_id) {
                    $user->visit_time= dateTrans($item->created_at);
                }
            });
        });

        return [
            'total'      => $total,
            'todayCount' => $count,
            'todayUser'  => count($data),
            'userList'   => UserCollection::collection($userList)
        ];
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * @return \Dingo\Api\Http\Response
     * 保存用户自定义表情
     */
    public function getEmoji()
    {
        $userId = auth()->id();
        $memKey = Constant::CUSTOM_USER_EMOJI.$userId;
        $result = Redis::get($memKey);
        $result = !empty($result) ? json_decode($result, true) : [];

        if (empty($result)) {
            $result = UserEmoji::select('id','emoji')->where('user_id', $userId)->orderByDesc('updated_at')->get();
            $result = $result->isEmpty() ? [] : $result->toArray();
            Redis::set($memKey, json_encode($result));
            Redis::expire($memKey, 86400*7);
        }
        return $result;
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 保存用户自定义表情
     */
    public function setEmoji(Request $request)
    {
        $userId = auth()->id();
        $emoji  = $request->input('emoji');
        UserEmoji::create(['user_id'=>$userId, 'emoji'=>$emoji]);
        Redis::del(Constant::CUSTOM_USER_EMOJI.$userId);
        return $this->response->accepted();
    }


    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 保存用户自定义表情
     */
    public function updateEmoji(Request $request)
    {
        $userId = auth()->id();
        $id     = $request->input('id');
        if (!empty($id)) {
            UserEmoji::where(['id'=>$id, 'user_id'=>$userId])->update();
            Redis::del(Constant::CUSTOM_USER_EMOJI.$userId);
        }
        return $this->response->accepted();
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,User $user)
    {
        $result = $this->user->update($user,$request->all());
        return $result;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function follow($user_id)
    {
        $follower = auth()->user();
        if(!app(UserRepository::class)->isProhibited($follower))
        {
            if($follower->user_id!=$user_id)
            {
                $user = $this->user->findOrFail($user_id);
                if(!app(UserRepository::class)->isProhibited($user))
                {
                    $follow = $follower->followUser($user);
                    if($follow===true)
                    {
                        event(new Follow($follower , $user));
                    }
                }
            }
        }
        return $this->response->noContent();
    }

    public function unfollow($user_id)
    {
        $follower = auth()->user();
        if($follower->user_id!=$user_id)
        {
            $user = $this->user->findOrFail($user_id);
            $unFollow = $follower->unFollowUser($user);
            if($unFollow===true)
            {
                event(new UnFollow($follower , $user));
            }
        }
        return $this->response->noContent();
    }

    public function myFollow()
    {
        return FollowCollection::collection($this->user->findMyFollow(auth()->id()));
    }

    public function followMe()
    {
        return FollowCollection::collection($this->user->findFollowMe(auth()->id()));
    }
    public function otherMyFollow($user_id)
    {
//        $user = $this->user->findOrFail($user_id);
        return FollowCollection::collection($this->user->findOtherMyFollow($user_id));
    }

    public function otherFollowMe($user_id)
    {
//        $user = $this->user->findOrFail($user_id);
        return FollowCollection::collection($this->user->findOtherFollowMe($user_id));
    }
    public function myFollowRandTwo()
    {
        $user = auth()->user();
        $userfollowrandtwo['user_myfollowcount'] = $this->userMyFollowCount($user->user_id);
        $userfollowrandtwo['user_myfollowrandtwo'] = $user->followings()->inRandomOrder()->take(2)->pluck('user_avatar_link');
        return $userfollowrandtwo;
    }

    public function rank(Request $request)
    {
        return UserCollection::collection($this->user->getUserRank());
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

    public function getQiniuUploadToken(Request $request)
    {
        $type = $request->input('type' , 1);
        $driver = $request->input('driver' , 'qn_avatar');
        if(!in_array($driver , array('qn_avatar' , 'qn_image' , 'qn_video')))
        {
            $driver = 'qn_avatar';
        }
        if($type==1)
        {
            $driver = $driver.'_sia';
        }
        $config = config('filesystems.disks.'.$driver);
        $key = "$(etag)$(ext)";
        $url = $config['domain'];
        $policy = [
            'saveKey'=>"$(etag)$(ext)",
//            'mimeLimit'=>'image/*',

            'forceSaveKey'=>true
        ];
        if(strpos($driver , 'video')===false)
        {
            $policy['mimeLimit'] = 'image/*';
            $policy['returnBody'] = "{\"key\": \"$key\", \"hash\": \"$(etag)\", \"w\": $(imageInfo.width),\"h\": $(imageInfo.height),\"size\": \"$(fsize)\",\"url\":\"$url\"}";
        }else{
            $policy['fsizeLimit'] = 52428800;
            $policy['mimeLimit'] = 'video/*';
            $policy['returnBody'] = "{\"key\": \"$key\", \"hash\": \"$(etag)\", \"size\": \"$(fsize)\",\"url\":\"$url\"}";
        }
        $disk = Storage::disk($driver);
        $token = $disk->getUploadToken(null , 3600 , $policy);
        return array('qntoken'=>$token);
    }

    public function profileLike($id)
    {
        $this->user->profileLike($id);
        return $this->response->noContent();
    }

    public function profileRevokeLike($id)
    {
        $this->user->profileRevokeLike($id);
        return $this->response->noContent();
    }

    public function cancelled($name , $email)
    {

    }

    public function randRyOnlineUser(Request $request)
    {
        $user_gender = $request->input('user_gender');
        if($request->has('country')&&$user_gender!==null)
        {
            $user = $this->user->randDiffRyOnlineUserV2();
            $user->user_country_id = $this->getUser($user->user_id , array('user_country_id'))['user_country_id'];
            return new UserCollection($user);
        }elseif ($request->has('hobby')){
            $user = $this->user->randDiffRyOnlineUserByHobby();
            return new UserCollection($user);
        }else{
            $userId = $this->user->randDiffRyOnlineUser();
        }
        if($userId>0)
        {
            $user = $this->user->findOrFail($userId);
            return new UserCollection($user);
        }else{
            return $this->response->errorNotFound();
        }

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
//        $users = array_pluck($users , 'status' , 'userid');
        $this->user->updateUserOnlineState($users);
        return $response->setStatusCode(200);
    }

    public function referFriend()
    {
        $users = $this->user->referFriend();
        return UserCollection::collection($users);
    }

    public function planet()
    {
        $data = $this->user->planet();
        $data = array_unique($data);
        $userId = intval(auth()->id());
        $data = array_diff($data , [$userId, 35525, 219367, 28583, 28527, 69684, 97623, 28761]);
        $users = $this->user->findByMany($data);
        $total = $this->user->onlineUsersCount();
        $users = UserCollection::collection($users)->additional(array(
            'total'=>$total
        ));
        return $users;
    }

    public function filter()
    {
        return $this->planet();
        $users = $this->user->filter();
        $total = $this->user->onlineUsersCount();
        $users = UserCollection::collection($users)->additional(array(
            'total'=>$total
        ));
        return $users;
    }
}
