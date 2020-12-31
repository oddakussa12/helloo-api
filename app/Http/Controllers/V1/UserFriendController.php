<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\Friend;
use App\Jobs\FriendLevel;
use App\Jobs\FriendSignIn;
use App\Models\UserFriend;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Resources\UserFriendCollection;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\UserFriendRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Jenssegers\Agent\Agent;

class UserFriendController extends BaseController
{
    /**
     * @var UserFriendRepository
     */
    private $userFriend;


    /**
     * UserFriendController constructor.
     * @param UserFriendRepository $userFriendRepository
     */
    public function __construct(UserFriendRepository $userFriend)
    {
        $this->userFriend = $userFriend;
    }


    /**
     * @param int $userId
     * @return mixed
     */
    public function index(int $userId)
    {
        $userFriends = $this->userFriend->paginateByUser($userId);
        $friendIds = $userFriends->pluck('friend_id')->all();
        $friends = app(UserRepository::class)->findByMany($friendIds);
        $userFriends->each(function($friend , $key) use ($friends){
            $friend->friend = $friends->where('user_id' , $friend->friend_id)->first();
        });
        $userFriends = $userFriends->filter(function ($userFriend, $key) {
            return !blank($userFriend->friend);
        });
        return UserFriendCollection::collection($userFriends);
    }

    /**
     * @param int $friendId
     * @return string
     */
    public function store(int $friendId)
    {
        return $this->response->f_friends_request();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function my(Request $request)
    {
        $userId = auth()->id();
        $userFriends = $this->userFriend->paginateByUser($userId);
        $friendIds = $userFriends->pluck('friend_id')->all();
        $friends = app(UserRepository::class)->findByMany($friendIds);
        $userFriends->each(function($userFriend , $key) use ($friends){
            $userFriend->friend = new UserCollection($friends->where('user_id', $userFriend->friend_id)->first());
        });
        return UserFriendCollection::collection($userFriends);
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * 修改好友备注名称
     */
    public function update(Request $request)
    {
        $userId   = auth()->id();
        $friendId = $request->input('friend_id');
        $nickName = $request->input('nick_name');

        $this->validate($request, [
            'friend_id' => 'required|int',
            'nick_name' => 'required|string|min:1',
        ]);

        UserFriend::where(['user_id'=>$userId, 'friend_id'=>$friendId])->update(['friend_nick_name'=>$nickName]);
        return $this->response->accepted();
    }

    /**
     * @param $friendId
     * @return \Dingo\Api\Http\Response
     * 删除用户及其相关
     */
    public function destroy($friendId)
    {
        $userId = auth()->id();
        $user   = auth()->user();

        if (empty($userId) || empty($friendId)) {
            return $this->response->noContent();
        }
        $flag = false;
        DB::beginTransaction();
        try {
            // 删除好友关系
            $userResult = DB::delete("delete from `t_users_friends` where `user_id`={$userId}   and `friend_id`={$friendId}");
            $friendResult = DB::delete("delete from `t_users_friends` where `user_id`={$friendId} and `friend_id`={$userId}");
            if($userResult>0&&$friendResult>0)
            {
                $flag = true;
                DB::commit();
            }else{
                throw new \Exception('userId:'.$userId.' and friendId:'.$friendId.' delete fail');
            }
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::error('friend_delete_failed' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
        }

        // 融云推送 聊天
        if($flag)
        {
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendDelete",
                'content'    => array(
                    'content'=>'friend delete',
                    'user'=> collect(new UserCollection($user))->toArray()
                ),
                'pushContent'=>'friend delete',
                'pushExt'=>\json_encode(array(
                    'title'=>'friend delete',
                    'forceShowPushContent'=>1
                ))
            );
            Log::info('delete_content' , $content);
            $result = app('rcloud')->getMessage()->System()->send($content);
            Log::info('delete_result' , $result);
        }
        return $this->response->noContent();
    }
}
