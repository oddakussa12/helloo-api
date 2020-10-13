<?php

namespace App\Console\Commands;


use App\Models\User;
use App\Traits\CachableUser;
use App\Models\ServiceMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;


class AllUserNotification extends Command
{
    use CachableUser;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'all:user_notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'All User Notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = "yooul_all_message";
        if(Redis::exists($key))
        {
            $messageId = intval(Redis::get($key));
            Redis::del($key);
            $message = ServiceMessage::find($messageId);
            if(!blank($message))
            {
                $message->status=2;
                $message->save();
                $senderId = 97623;
                $lastActivityTime = 'ry_user_last_activity_time';
                $pushTime = $this->pushTime(500);
                $start = $pushTime['start'];
                $end = $pushTime['end'];
                $options = array('withScores'=>true);
                $limit = 200;
                $count = Redis::zcount($lastActivityTime , $start , $end);
                $lastPage = intval(ceil($count/$limit));
                $person = app('rcloud')->getMessage()->Person();
                $objectName = "Yooul:ServiceMessage";
                $user = $this->getUser($senderId , array('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar' , 'user_country_id' , 'user_level' , 'user_gender'));
                if(empty($user['user_id']))
                {
                    $user = collect(User::find($senderId))->toArray();
                }
                if(blank($user))
                {
                    return;
                }
                $extra = array(
                    'un' => !empty($user['user_nick_name'])?$user['user_nick_name']:(empty($user['user_name'])?'Yooul_Service':$user['user_name']),
                    'ua' => userCover($user['user_avatar'] ?? ''),
                    'ui' => $user['user_id'],
                    'uc' => getUserCountryId($user['user_country_id']),
                    'ul' => $user['user_level'],
                    'ug' => $user['user_gender'],
                    'devicePlatformName' => 'Server',
                );
                $msg = collect($message)->only('value' , 'type' , 'title' , 'content' , 'image')->toArray();
                if(blank($msg['image']))
                {
                    unset($msg['image']);
                }
                $content = array(
                    'userInfo'=>array(
                        'extra'=>$extra
                    )
                );
                $content = array_merge($content , $msg);
                for ($page=1;$page<=$lastPage;$page++)
                {
                    $userIds = array();
                    $offset = ($page-1)*$limit;
                    $options['limit'] = array($offset , $limit);
                    $users = Redis::zrangebyscore($lastActivityTime , $start , $end , $options);
                    foreach ($users as $user_id=>$lastTime)
                    {
                        array_push($userIds, $user_id);
                    }
                    $userIds = array_diff($userIds , array($senderId));
                    if(!blank($userIds))
                    {
                        $result = $person->send(array(
                            'senderId'   => $senderId,
                            'targetId'   => $userIds,
                            "objectName" => $objectName,
                            'content'    => \json_encode($content)
                        ));
                    }
                }
                $message->status=3;
                $message->save();
            }
        }

    }

    private function pushTime(int $day=1)
    {
        $start = Carbon::now()->subDays($day)->startOfDay()->timestamp;
        $end = Carbon::now()->subDays($day)->endOfDay()->timestamp;
        return compact('start' , 'end');
    }

}
