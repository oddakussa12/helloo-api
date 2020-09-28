<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\Affinity;
use App\Jobs\Friend;
use App\Jobs\FriendLevel;
use App\Jobs\FriendSignIn;
use App\Jobs\RySystem;
use App\Models\User;
use App\Models\UserFriend;
use App\Models\UserFriendLevel;
use App\Models\UserFriendRelationRule;
use App\Models\UserFriendRelationship;
use App\Models\UserFriendRelationShipRule;
use App\Models\UserFriendSignIn;
use App\Models\UserFriendTalkList;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\StoreUserFriendRequestRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Jenssegers\Agent\Agent;

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

        if ($authUserId == $friendId) {
            //return $this->response->noContent();
        }

        list($user_id, $friend_id) = FriendSignIn::sortId($authUserId, $friendId);

        $memKey   = Constant::FRIEND_RELATIONSHIP_MAIN.$user_id.'_'.$friend_id;
        $memValue = Redis::get($memKey);
        if (!empty($memValue)) {
            //return json_decode($memValue, true);
        }

        $baseWhere= ['user_id'=>$user_id,'friend_id'=>$friend_id,'is_delete'=>0];

        $result   = UserFriendLevel::select('heart_count','relationship_id')
            ->where(array_merge($baseWhere, ['status'=>1]))->first();

        if (!empty($result)) {
            $result['sign'] = $this->getSignInList($friendId, false);
        } else {
            $talk = UserFriendTalkList::select('score')->where(array_merge($baseWhere, ['score'=>1]))->first();
            $result['heart_count']     = !empty($talk) ? 1 : 0;
            $result['relationship_id'] = -1;
            $result['sign']['total']   = 0;
        }

        $isFriend = FriendSignIn::isFriend($user_id, $friend_id);

        // 不是双方好友关系
        if (!empty($isFriend)) {
            $isFriend   = json_decode($isFriend, true);
            $createTime = $isFriend['user']['created_at'] > $isFriend['friend']['created_at'] ? $isFriend['user']['created_at'] : $isFriend['friend']['created_at'];
        }
        $result['friend_time'] = !empty($createTime) ? intval((time() - $createTime)/86400) : 0;

        $friend           = User::where('user_id', $friendId)->first();
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
        if (empty(intval($friendId))) {
            return $this->response->noContent();
        }
        $authUserId = auth()->id();
        list($userId, $friendId)   = FriendSignIn::sortId($authUserId, $friendId);

        $baseWhere = ['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0];

        $result    =  UserFriendLevel::select('heart_count','relationship_id')
            ->where(array_merge($baseWhere, ['status'=>1]))->first();

        $count = UserFriendTalkList::select('user_id','user_id_count', 'friend_id', 'friend_id_count', DB::RAW('score as heart_count'))
            ->where($baseWhere)->first();

        $heartCount = !empty($result) ? $result['heart_count'] : $count['heart_count'];
        $count['heart_count']     = intval($heartCount);
        $count['relationship_id'] = !empty($result) ? $result['relationship_id'] : -1;

        return ['data'=>$count];

    }

    /**
     * @param Request $request
     * @return void 等级规则
     * 等级规则
     */
    public function rule(Request $request)
    {
        $type     = intval($request->input('type'));
        $userId   = intval($request->input('user_id'));
        $friendId = intval($request->input('friend_id'));

        dump($userId, $friendId);
        if ($type==1) {
            $raw['fromUserId'] = $userId;
            $raw['toUserId']   = $friendId;
            dump('第一次：'.$userId. '>>>>>>'. $friendId);
            $result = (new FriendLevel($raw))->handle();
            $raw['fromUserId'] = $friendId;
            $raw['toUserId']   = $userId;
            dump('第二次：'.$friendId. '>>>>>>'. $userId);

            $result2 = (new FriendLevel($raw))->handle();
        } elseif($type==2) {
            $raw['fromUserId'] = $userId;
            $raw['toUserId']   = $friendId;
            dump('队列第一次：'.$userId. '>>>>>>'. $friendId);
            $result = FriendLevel::dispatch($raw)->onConnection('sqs')->onQueue(Constant::QUEUE_FRIEND_LEVEL);
            $raw['fromUserId'] = $friendId;
            $raw['toUserId']   = $userId;
            dump('队列第二次：'.$friendId. '>>>>>>'. $userId);
           $result2 = FriendLevel::dispatch($raw)->onConnection('sqs')->onQueue(Constant::QUEUE_FRIEND_LEVEL);

        } elseif($type==3) {
            $raw['fromUserId'] = $friendId;
            $raw['toUserId']   = $userId;
            // 发送升级请求给双方 融云
            $ryData = [
                'heart_count'     => rand(1,10),
                'relationship_id' => rand(1,4),
            ];

            $result   = (new FriendLevel($raw))->sendMsgToRongYun($userId, $friendId, 'RC:CmdMsg', $ryData);
            $result2  = (new FriendLevel($raw))->sendMsgToRongYun($friendId, $userId, 'RC:CmdMsg', $ryData);
        } else {
            // 发送升级请求给双方 融云
            $ryData = [
                'heart_count'     => rand(1,10),
                'relationship_id' => rand(1,4),
            ];

            $result  = $this->sendMsgToRongYun($userId, $friendId, 'RC:CmdMsg', $ryData);
            $result2 = $this->sendMsgToRongYun($friendId, $userId, 'RC:CmdMsg', $ryData);
        }

        dump($result, $result2);

    }


    public function sendMsgToRongYun($userId, $friendId, $objectName, $data)
    {
        $user = Redis::hgetall('user.'.$userId.'.data');
        // 融云推送 聊天
        $this->dispatch((new RySystem($userId, $friendId, $objectName, [
            'name'     => 'HEART_UPGRADE',
            'data'     => $data,
            'userInfo' => $user
        ]))->onQueue(Constant::QUEUE_RY_CHAT_FRIEND));
    }

    /**
     * @param $friendId
     * @param bool $list
     * @return mixed
     * 获取好友间签到记录
     */
    public function getSignInList($friendId, $list=true)
    {
        $userId = auth()->id();

        list($userId, $friendId)  = FriendSignIn::sortId($userId, $friendId);
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
        $userId   = intval($userId);
        if (empty($userId)) return $this->response->array([]);

        $memKey   = Constant::FRIEND_RELATIONSHIP_HOME_TOP.$userId;
        $memValue = Redis::get($memKey);
        if (!empty($memValue)) {
            //return json_decode($memValue, true);
        }

        $result   = UserFriendLevel::select('user_id', 'friend_id', 'heart_count', 'relationship_id')
            ->whereRaw("(user_id= $userId or friend_id= $userId)")
            ->where(['is_delete'=>0,'status'=>1])->orderBy('heart_count', 'DESC')->limit(5)->get();

        $userIds   = $result->pluck('user_id')->all();
        $friendIds = $result->pluck('friend_id')->all();
        $result    = $result->toArray();
        $friendIds = array_unique(array_merge($userIds, $friendIds));
        $friendIds = array_filter($friendIds,
            function($value) use ($userId) {if (!empty($value) && $value != $userId) return $value;
        });

        $userInfo  = Redis::hgetAll("user.".strval($userId).'.data');
        $users     = app(UserRepository::class)->findByMany($friendIds);
        $users     = UserCollection::collection($users);

        foreach ($result as $index=>&$value) {
            $user_id         = $value['user_id'] == $userId ? $value['friend_id'] : $value['user_id'];
            $value['sign']   = $this->getSignInList($user_id, false);
            $value['friend'] = $users->where('user_id', $user_id)->first();
            $value['user_avatar'] = userCover($userInfo['user_avatar'] ?? '');
        }

        Redis::set($memKey, json_encode($result, JSON_UNESCAPED_UNICODE));
        Redis::expire($memKey, 86400);
        return $result;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $auth        = auth()->user();
        $authUserId  = $auth->user_id;

        $friend_id   = intval($request->input('friend_id'));
        $relation_id = intval($request->input('relationship_id'));
        $this->validate($request, [
            'relationship_id' => 'required|int',
            'friend_id'       => 'required|int|min:2',
        ]);

        if (empty($authUserId)) {
            Log::info('message::: auth()->user()->user_id 不为空');
            return $this->response->errorForbidden();
        }

        $isFriend = FriendSignIn::isFriend($authUserId, $friend_id);
        // 不是双方好友关系，直接返回
        if (empty($isFriend)) {
            Log::info('message::不是好友关系');
            return $this->response->errorNotFound(trans('FriendBig.you_are_not_friends_yet'));
        }

        if (!in_array($relation_id, Constant::$relation)) {
            //return $this->response->noContent();
            Log::info('message::该关系不存在::relationship_id::'.$relation_id);
            return $this->response->errorNotFound(trans('FriendBig.the_relationship_does_not_exist'));
        }

        list($userId, $friendId) = FriendSignIn::sortId($authUserId, $friend_id);

        $userFriend = FriendLevel::isFriendRelation($userId, $friendId);
        // 已是好友，直接返回
        if (!empty($userFriend)) {
            Log::info('11111111111111111111');
            Log::info('message::: is not empty 关系已经存在，不能添加');
            return $this->response->errorNotFound(trans('FriendBig.there_can_only_be_one_relationship'));
        }

        $relationShipFriend = $this->checkFriendLevel($friend_id, $relation_id, true);
        $relationShipUser   = $this->checkFriendLevel($authUserId  , $relation_id, true);


        if (empty($relationShipUser) || empty($relationShipFriend)) {
            Log::info('message::关系超限，不能添加');
            return $this->response->errorNotFound('关系超限，不能添加');
            return $this->response->noContent();
        }

        /*$requests = new UserFriendLevel();
        $requests->user_id         = $userId;
        $requests->friend_id       = $friendId;
        $requests->relationship_id = $relation_id;
        $requests->save();*/

        $baseWhere = ['user_id'=>$userId, 'friend_id'=>$friendId, 'relationship_id'=>$relation_id, 'status'=>0, 'is_delete'=>0];
        $level  = UserFriendLevel::where($baseWhere)->first();

        UserFriendLevel::updateOrCreate(['id' => $level['id'] ?? null], array_merge($baseWhere, ['heart_count'=>1]));

        // 融云推送 聊天
        $this->dispatch((new Friend($authUserId, $friend_id, 'Yooul:AffinityFriend', [
            'content'        => 'friend request',
            'relationship_id'=> $relation_id,
            'userInfo'       => $auth
        ]))->onQueue(Constant::QUEUE_RY_CHAT_FRIEND));

        // 推送通知
        /*if (Constant::QUEUE_PUSH_TYPE == 'redis') {
            $this->dispatch((new Affinity($user, $requests, 'friend_request'))->onQueue(Constant::QUEUE_PUSH_FRIEND));
        } else {
            $this->dispatch((new Affinity($user, $requests, 'friend_request'))->onConnection('sqs')->onQueue(Constant::QUEUE_PUSH_FRIEND));
        }*/

        Log::info('message::: 添加好友结束');
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

        list($user_id, $friend_id) = $arr = FriendSignIn::sortId($userId, $friendId);

        $relation_id = intval($request->input('relationship_id'));

        if (!in_array($relation_id, Constant::$relation)) {
            //return $this->response->noContent();
            return $this->response->errorNotFound(trans('FriendBig.the_relationship_does_not_exist'));
        }

        $isFriend = FriendSignIn::isFriend($user_id, $friend_id);

        // 不是双方好友关系，直接返回
        if (empty($isFriend)) {
            Log::info('message::不是好友关系');
            return $this->response->errorNotFound(trans('FriendBig.you_are_not_friends_yet'));
        }
        $baseWhere = ['user_id'=>$user_id,'friend_id'=>$friend_id, 'is_delete'=>0, 'status'=>0];
        $info      = UserFriendLevel::where(array_merge($baseWhere, ['relationship_id'=> $relation_id]))->first();

        // 邀请关系失效，直接返回
        if (empty($info)) {
            return $this->response->errorNotFound(trans('FriendBig.the_request_has_expired_please_resend_the_request'));
            Log::info('关系已完成或失效，不能添加');
            return $this->response->noContent();
        }

        $relationShipFriend = $this->checkFriendLevel($friendId, $relation_id, true);
        $relationShipUser   = $this->checkFriendLevel($userId  , $relation_id, true);

        if (empty($relationShipFriend) || empty($relationShipUser)) {
            Log::info('message::关系超限，不能添加');
            UserFriendLevel::where($baseWhere)->update(['status'=>-2]);
            return $this->response->errorNotFound('关系超限，不能添加');
            return $this->response->noContent();
        }

        // 修改关系为已同意
        $result = UserFriendLevel::where(array_merge($baseWhere, ['relationship_id'=> $relation_id]))->update(['status'=>1]);

        // 删除和该用户的，其他已存在的请求
        $result && UserFriendLevel::where($baseWhere)->update(['is_delete'=>-1]);

        // 融云推送 聊天
        $this->dispatch((new Friend($userId, $friendId, 'Yooul:AffinityFriendReposed', [
            'content'        => 'friend response',
            'reposed'        => 1,
            'relationship_id'=> $info['relationship_id'],
            'userInfo'       => $user
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
            $result->where('relationship_id', $relationShipId);

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
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 拒绝/忽略邀请
     */
    public function refuse($friendId, Request $request)
    {
        $auth        = auth()->user();
        $userId      = $auth->user_id;
        $relation_id = intval($request->input('relationship_id'));

        if (empty($userId) || empty($relation_id)) {
            return $this->response->errorForbidden();
        }

        list($user_id, $friend_id) = FriendSignIn::sortId($userId, $friendId);

        UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friend_id, 'relation_id'=>$relation_id, 'is_delete'=>0, 'status'=>0])->update(['status'=>-1,'is_delete'=>-1]);
        return $this->response->accepted();
    }
}
