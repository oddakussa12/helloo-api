<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Models\UserFriendRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AutoFriend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $to;
    private $request;

    public function __construct($to , $request)
    {
        $this->to = $to;
        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $requestId = $this->request;
        $user   = $this->to;
        $userId = $user->user_id;
        $userRequest = new UserFriendRequest();
        $request = $userRequest->where('request_id' , $requestId)->first();
        if(empty($request)||$request->request_state!=0||$request->request_to_id!=$userId)
        {
            return;
        }
        $friendId = $request->request_from_id;
        $state  = 1;
        $flag = true;
        $now = Carbon::now()->timestamp;
        DB::beginTransaction();
        try{
            $friendRequest = DB::table('friends_requests')->where('request_id', $requestId)->update([
                'request_state'=>$state,
                'request_updated_at'=>$now,
            ]);
            if($friendRequest>0)
            {
                $userFriend = DB::table('users_friends')->where('user_id', $userId)->where('friend_id', $friendId)->first();
                if(blank($userFriend))
                {
                    DB::table('users_friends')->insert(array(
                        array('user_id'=>$userId , 'friend_id'=>$friendId , 'created_at'=>$now)
                    ));
                }else{
                    if($userFriend->relation==0)
                    {
                        DB::table('users_friends')->where('id' , $userFriend->id)->update(array(
                            'relation'=>1
                        ));
                    }
                }
                $friendUser = DB::table('users_friends')->where('user_id', $friendId)->where('friend_id', $userId)->first();
                if(blank($friendUser))
                {
                    DB::table('users_friends')->insert(array(
                        array('user_id'=>$friendId , 'friend_id'=>$userId , 'created_at'=>$now)
                    ));
                }else{
                    if($friendUser->relation==0)
                    {
                        DB::table('users_friends')->where('id' , $friendUser->id)->update(array(
                            'relation'=>1
                        ));
                    }
                }
            }else{
                $flag = false;
            }
            DB::commit();
            $mKey = 'helloo:account:user-friends';
            Redis::del($mKey.$userId);
            Redis::del($mKey.$friendId);
        }catch (\Exception $e)
        {
            DB::rollBack();
            $flag = false;
            Log::info('friend_request_accept_failed' , array(
                'requestId'=>$requestId,
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
        }
        if($flag)
        {
            FriendFromDifferentSchool::dispatch($user , $friendId)->onQueue('helloo_{friend_from_different_school}');
            MoreTimeUserScoreUpdate::dispatch($user->user_id , 'friendAccept' , $friendId)->onQueue('helloo_{more_time_user_score_update}');
            MoreTimeUserScoreUpdate::dispatch($friendId , 'friendAccepted' , $user->user_id)->onQueue('helloo_{more_time_user_score_update}');
            $phone = DB::table('users_phones')->where('user_id' , $userId)->first();
            $country = $phone->user_phone_country;
            if(!in_array($country , array(670 , '670' , '62' , 62 , '251' , 251)))
            {
                $country = 'other';
            }
            $key = "helloo:account:game:country:score:coronation".'-'.$country;
            $sortKey = "helloo:account:friend:game:rank:sort:".$userId.'-coronation';//暂时一个游戏
            $friendSortKey = "helloo:account:friend:game:rank:sort:".$friendId.'-coronation';//暂时一个游戏
            $maxScore = Redis::zscore($key , $userId);
            $friendMaxScore = Redis::zscore($key , $friendId);
            if($maxScore!==null)
            {
                Redis::exists($friendSortKey)&&Redis::zadd($friendSortKey , $maxScore , $userId);
            }
            if($friendMaxScore!==null)
            {
                Redis::exists($sortKey)&&Redis::zadd($sortKey , $friendMaxScore , $friendId);
            }
            $user = collect(new UserCollection($user))->toArray();
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendRequestResponse",
                'content'    => array(
                    'response'  => 1,
                    'content'=>'friend response',
                    'request_id'=>$requestId,
                    'user'=> $user
                ),
                'pushContent'=>'friend response',
                'pushExt'=>\json_encode(array(
                    'title'=>'friend response',
                    'forceShowPushContent'=>1
                ))
            );
            Log::info('accept_content' , $content);
            $result = app('rcloud')->getMessage()->System()->send($content);
            Log::info('accept_result' , $result);
        }
    }

}
