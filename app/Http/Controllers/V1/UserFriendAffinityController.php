<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\Affinity;
use App\Jobs\Friend;
use App\Jobs\FriendSignIn;
use App\Models\User;
use App\Models\UserFriend;
use App\Models\UserFriendLevel;
use App\Models\UserFriendRelationRule;
use App\Models\UserFriendRelationship;
use App\Models\UserFriendRelationShipRule;
use App\Models\UserFriendSignIn;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\StoreUserFriendRequestRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Class UserFriendAffinityController
 * @package App\Http\Controllers\V1
 * 特殊好友关系
 */
class UserFriendAffinityController extends BaseController
{

    public function __construct()
    {
    }

    /**
     * @param $friendId
     * @return array
     */
    public function main($friendId)
    {
        $authUserId = auth()->id();
        list($user_id, $friend_id) = FriendSignIn::sortId($authUserId, $friendId);

        $memKey   = Constant::FRIEND_RELATIONSHIP_MAIN.$user_id.'_'.$friend_id;
        $memValue = Redis::get($memKey);
        if (!empty($memValue)) {
            return json_decode($memValue, true);
        }

        $result   = UserFriendLevel::select('heart_count','relationship_id')
            ->where(['user_id'=>$user_id,'friend_id'=>$friend_id,'is_delete'=>0,'status'=>1])->first();

        if (!empty($result)) {
            $result['sign'] = $this->getSignInList($friendId, false);
        } else {
            $result['heart_count']     = 0;
            $result['relationship_id'] = -1;
            $result['sign']['total']   = 0;
        }

        $userFriend = UserFriend::where('user_id', $authUserId)->where('friend_id' , $friendId)->first();
        $friendUser = UserFriend::where('user_id', $friendId)->where('friend_id' , $authUserId)->first();

        $createTime = $userFriend['created_at'] > $friendUser['created_at'] ? $userFriend['created_at'] : $friendUser['created_at'];
        $result['friend_time'] = intval((time() - $createTime)/86400);

        $uid              = $user_id == $authUserId ? $user_id : $friendId;
        $friend           = User::where('user_id', $uid)->first();
        $result['friend'] = new UserCollection($friend);

        Redis::set($memKey, json_encode($result, JSON_UNESCAPED_UNICODE));
        Redis::expire($memKey, 86400);
        return $result ?? [];

    }


    /**
     * @param $friendId
     * @return mixed
     * 获取心❤的数量
     */
    public function heart($friendId)
    {
        $authUserId = auth()->id();
        list($userId, $friendId)   = FriendSignIn::sortId($authUserId, $friendId);

        return UserFriendLevel::select('heart_count','relationship_id')
            ->where(['user_id'=>$userId,'friend_id'=>$friendId,'is_delete'=>0,'status'=>1])->first();

    }

    /**
     * @return array
     * 等级规则
     */
    public function rule()
    {
        for ($i=1;$i<5;$i++) {
            for ($j=1;$j<4;$j++) {
                $result[] = [
                    "relationship_id" => $i,
                    "title"           => "黑铁".$i.$j,
                    "level"           => $j,
                    "content"         =>"黑铁".$i.$j
                ];
            }
        }
        return [
            'data'=>$result ?? [],
            'rule'=>'-----------------',
        ];
    }

    /**
     * @param $friendId
     * @param bool $num
     * @return mixed
     * 获取好友间签到记录
     */
    public function getSignInList($friendId, $list=true)
    {
        $userId = auth()->id();

        $result = UserFriendSignIn::select('sign_day')->where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0])
            ->orderBy('id', 'ASC')->limit(30)->get()->toArray();

        $firstDay = current($result);
        $endDay   = end($result);
        $today    = strtotime(date('Ymd'));

        $totalDay = ($today - $firstDay['sign_day'])/86400;
        $totalDay = $today == $endDay['sign_day'] ? $totalDay +1 : $totalDay;

        $signDay  = count($result);
        $total    = $signDay - ($totalDay - $signDay);
        $total    = $total < 0 ? 0 : $total;

        $data['total']  = $total;
        if ($list) {
            $data['list']   = array_map(function($val){
                return date('Ymd', $val['sign_day']);
            }, $result);
        }

        return $data;
    }

    /**
     * @param $userId
     * @return mixed
     * 获取特殊好友关系列表 TOP5
     */
    public function top($userId)
    {
        $userId = intval($userId);
        if (empty($userId)) return $this->response->array([]);

        $result   = UserFriendLevel::select('user_id', 'friend_id', 'heart_count', 'relationship_id')
            ->where('user_id', $userId)->orWhere('friend_id', $userId)
            ->where(['is_delete'=>0,'status'=>1])->orderBy('heart_count', 'DESC')->limit(5)->get();

        $userIds   = $result->pluck('user_id')->all();
        $friendIds = $result->pluck('friend_id')->all();
        $result    = $result->toArray();
        $friendIds = array_unique(array_merge($userIds, $friendIds));
        $friendIds = array_filter($friendIds,
            function($value) use ($userId) {if (!empty($value) && $value != $userId) return $value;
        });

        $users     = app(UserRepository::class)->findByMany($friendIds);
        $users     = UserCollection::collection($users);

        foreach ($result as $index=>&$value) {
            $user_id         = $value['user_id'] == $userId ? $value['friend_id'] : $value['user_id'];
            $value['sign']   = $this->getSignInList($user_id, false);
            $value['friend'] = $users->where('user_id', $user_id)->first();
        }

        return $result;

        /*$friends = app(UserRepository::class)->findByMany($friendIds);
        $userFriendRequests = $result->filter(function ($value, $key) {
            return !blank($value->friend);
        });
        return UserFriendCollection::collection($userFriendRequests);*/
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $friendId    = intval($request->input('friend_id'));
        $relation_id = intval($request->input('relationship_id'));
        $this->validate($request, [
            'relationship_id' => 'required|int',
            'friend_id'       => 'required|int',
        ]);

        $relation    = UserFriendRelationship::where(['is_delete'=>0,'id'=>$relation_id])->first();

        if (empty($relation)) {
            return $this->response->noContent();
            return $this->response->errorNotFound('该关系不存在');
        }
        $auth = auth()->user();
        list($userId, $friendId) = FriendSignIn::sortId($auth->user_id, $friendId);

        $requests = UserFriendLevel::where(['user_id'=>$userId,'friend_id'=>$friendId,'is_delete'=>0])->where('status', '>=', 0)->first();
        if (!empty($request)) {
            return $this->response->accepted();
        }

        $relationShipFriend = $this->checkFriendLevel($friendId, $relation_id, true);
        $relationShipUser   = $this->checkFriendLevel($userId  , $relation_id, true);

        if (empty($relationShipFriend) || empty($relationShipUser)) {
            Log::info('message::关系超限，不能添加');
            //return $this->response->errorNotFound('关系超限，不能添加');
            return $this->response->noContent();
        }

        $requests->user_id         = $userId;
        $requests->friend_id       = $friendId;
        $requests->relationship_id = $relation_id;
        $requests->save();
        $user = new UserCollection($auth);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );

        // 融云推送 聊天
        $this->dispatch((new Friend($auth->user_id, $friendId, 'Yooul:AffinityFriendRequest', [
            'content' => 'friend request',
            'user'    => $user
        ]))->onQueue(Constant::QUEUE_RY_CHAT_FRIEND));

        // 推送通知
        /*if (Constant::QUEUE_PUSH_TYPE == 'redis') {
            $this->dispatch((new Affinity($user, $requests, 'friend_request'))->onQueue(Constant::QUEUE_PUSH_FRIEND));
        } else {
            $this->dispatch((new Affinity($user, $requests, 'friend_request'))->onConnection('sqs')->onQueue(Constant::QUEUE_PUSH_FRIEND));
        }*/
        return $this->response->created();
    }

    /**
     * @param $friendId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 接受特殊关系请求
     */
    public function accept($friendId, Request $request)
    {
        $user   = auth()->user();
        $userId = $user->user_id;
        $arr    = [$user->user_id, $friendId];
        sort($arr);
        list($user_id, $friend_id) = $arr;

        $userFriend = UserFriend::where('user_id', $userId)->where('friend_id' , $friendId)->first();
        $friendUser = UserFriend::where('user_id', $friendId)->where('friend_id' , $userId)->first();

        // 不是双方好友关系，直接返回
        if (empty($userFriend) || empty($friendUser)) {
            Log::info('message::不是好友关系');
            return $this->response->errorNotFound('不是好友关系');
        }
        $info = UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friend_id, 'is_delete'=>0, 'status'=>0])->first();

        // 邀请关系失效，直接返回
        if (empty($info)) {
            // return $this->response->errorNotFound('关系已完成或失效，不能添加');
            Log::info('关系已完成或失效，不能添加');
            return $this->response->noContent();
        }

        $relationShipFriend = $this->checkFriendLevel($friendId, $info['relationship_id'], true);
        $relationShipUser   = $this->checkFriendLevel($userId  , $info['relationship_id'], true);

        if (empty($relationShipFriend) || empty($relationShipUser)) {
            Log::info('message::关系超限，不能添加');
            UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friend_id, 'is_delete'=>0, 'status'=>0])->update(['status'=>-2]);
            // return $this->response->errorNotFound('关系超限，不能添加');
            return $this->response->noContent();
        }


        UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friend_id, 'is_delete'=>0, 'status'=>0])->update(['status'=>1]);

        $user = new UserCollection($user);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );


        // 融云推送 聊天
        $this->dispatch((new Friend($userId, $friendId, 'Yooul:AffinityFriendRequestReposed', [
            'content' => 'friend response',
            'reposed' => 1,
            'user'    => $user
        ]))->onQueue(Constant::QUEUE_RY_CHAT_FRIEND));

        // 推送通知
        /*if (Constant::QUEUE_PUSH_TYPE == 'redis') {
            $this->dispatch((new Affinity($user, $request->all(), 'accept_friend_request'))->onQueue(Constant::QUEUE_PUSH_FRIEND));
        } else {
            $this->dispatch((new Affinity($user, $request->all(), 'accept_friend_request'))->onConnection('sqs')->onQueue(Constant::QUEUE_PUSH_FRIEND));
        }*/

        return $this->response->accepted();
    }

    /**
     * @param $userId
     * @param string $relationShipId
     * @param bool $compare
     * @return int
     * 查询特殊关系数量
     */
    public function checkFriendLevel($userId, $relationShipId='', $compare=false)
    {
        $result = UserFriendLevel::select('user_id', 'friend_id', 'score', 'relationship_id')
            ->where('user_id', $userId)->orWhere('friend_id', $userId);
        if ($relationShipId)
            $result->where('relationship_id', 0);

        $data = $result->where(['is_delete'=>0,'status'=>1])->groupBy('relationship_id')->get()->toArray();

        // 判断是否可以新增关系， true为可以 false为数量已满
        if ($relationShipId) {
            $count = count($data);
            if (empty($count)) return true;

            if ($compare) {
                if (in_array($relationShipId, Constant::$relation) && $count < Constant::$relationSum[$relationShipId]) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return $count;
            }
        }
        return $data;

    }

    /**
     * @param $friendId
     * @return \Dingo\Api\Http\Response
     * 拒绝/忽略邀请
     */
    public function refuse($friendId)
    {
        $auth = auth()->user();
        $arr  = [$auth->user_id, $friendId];
        sort($arr);
        list($user_id, $friendId) = $arr;

        UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friendId, 'is_delete'=>0, 'status'=>0])->update(['status'=>-1,'is_delete'=>-1]);
        return $this->response->accepted();
    }
}
