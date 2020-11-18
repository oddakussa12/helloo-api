<?php

namespace App\Http\Controllers\V1;


use Illuminate\Queue\RedisQueue;
use App\Repositories\Contracts\UserRepository;
use App\Resources\UserCollection;
use Illuminate\Http\Request;
use App\Messages\SignInMessage;
use App\Custom\EasySms\PhoneNumber;
use App\Custom\Uuid\RandomStringGenerator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;



class TestController extends BaseController
{
    public function index()
    {
        $sms = app('easy-sms');
        $number = new PhoneNumber(7529908826 , 44);
        try{
            $result = $sms->send($number, new SignInMessage(1234) , array('yunXinCustom'));
            Log::error($result);
        }catch (NoGatewayAvailableException $e)
        {
            $exception = $e->getLastException();
            Log::error($exception->getMessage());
        }

    }
    public function redis()
    {
        $period = 1000;
        $key = "test_{test}";
        $this->now = $now = millisecond();   # 毫秒时间戳
        $redis = Redis::connection('single');
        $redis->multi(); //使用管道提升性能
        $redis->zadd($key, $now, $now); //value 和 score 都使用毫秒时间戳
        $redis->zremrangebyscore($key, 0, $now - $period * 1000); //移除时间窗口之前的行为记录，剩下的都是时间窗口内的
        $redis->zcard($key);  //获取窗口内的行为数量
        $redis->zrangebyscore($key , "-inf" , "+inf" , array(
            'withScores'=>true,
            'limit'=>array(0,1)
        ));
        $redis->expire($key, $period  + 1);  //多加一秒过期时间
        $replies = $redis->exec();
        return array('count'=>intval($replies[2]) , 'first'=>array_first($replies[3]));
    }
    public function push()
    {
        $user = app(UserRepository::class)->find(94);
        $content = array(
            'content'=>'test message',
            'userInfo'=>[
                'extra'=> [
                    'un' => !empty($user->user_nick_name) ? $user->user_nick_name : ($user->user_name ?? ''),
                    'ua' => userCover($user->user_avatar ?? ''),
                    'ui' => $user->user_id,
                    'ug' => $user->user_gender,
                    'devicePlatformName' => 'server',
                ]
            ]
        );
        $content = \json_encode($content , JSON_UNESCAPED_UNICODE);
        $result = app('rcloud')->getMessage()->Broadcast()->recall(array(
            'senderId'   => 'system',
//            'targetId'   => $this->targetId,
            "objectName" => "RC:SightMsg",
            'content'    => \json_encode(array(
                'sightUrl'=>1,
                'content'=>1,
                'duration'=>1,
                'size'=>1,
                'name'=>1,
                'user'=>array(
                    'id'=>1
                ),
                'extra'=>'extra'
            ))
,
            'extra'=>array('user'=>1234567890),
        ));
        Log::error($content);
        Log::error(\json_encode($result , JSON_UNESCAPED_UNICODE));
    }


    public function token()
    {
        Log::error($this->redis());
        if(domain()!=config('app.url'))
        {
//            $token = app('rcloud')->getUser()->register(array(
//                'id'=> time(),
//                'name'=> (new RandomStringGenerator())->generate(16),
//                'portrait'=> "https://qnwebothersia.mmantou.cn/default_avatar.jpg?imageView2/0/w/50/h/50/interlace/1|imageslim"
//            ));
//            return $this->response->array($token);
        }
    }


}
