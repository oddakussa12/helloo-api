<?php

namespace App\Http\Controllers\V1;


use Vonage\Voice\NCCO\NCCO;
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
        $key = "test";
        $this->now = $now = millisecond();   # 毫秒时间戳

        $redis = app('redis')->connection('default');
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
//        return array('count'=>intval($replies[2]) , 'first'=>array_first($replies[3]));
    }

    public function broadcast()
    {
        $content = array(
            'senderId'   => 334,
            "objectName" => "Helloo::BroadcastVideoUrl",
            'content'    => array(
                'content'=>'Whole network push_'.mt_rand(1111 , 9999),
                'videoUrl'=>"https://f.video.weibocdn.com/R3Rjmslrlx07IsJmPztu01041200743K0E010.mp4?label=mp4_hd&template=480x640.24.0&trans_finger=7c347e6ee1691b93dc7e5726f4ef34b3&ori=0&ps=1EO8O2oFB1ePdo&Expires=1606974751&ssig=9csHBh3jaL&KID=unistore,video"
            ),
            'pushContent'=>'A test push notification content',
            'pushExt'=>array(
                'title'=>'A test push notification title',
                'forceShowPushContent'=>1
            )
        );
        $result = app('rcloud')->getMessage()->System()->broadcast($content);
        Log::error($content);
        Log::error(\json_encode($result , JSON_UNESCAPED_UNICODE));
    }

    public function push(Request $request)
    {
        $userId = intval($request->input('userId' , 0));
        $userId = $userId<=0?94:$userId;
        $user = app(UserRepository::class)->findOrFail($userId);
        $auth = app(UserRepository::class)->findOrFail(61);
        $content = array(
            'senderId'   => 'System',
            'targetId'   => $userId,
            "objectName" => "Helloo:UserReported",
            'content'    => \json_encode(array(
                'content'=>'You have been reported',
                'whistleblower'=> new UserCollection($auth)
            )),
            'pushContent'=>'You have been reported',
            'pushExt'=>\json_encode(array(
                'title'=>'You have been reported',
                'forceShowPushContent'=>1
            ))
        );
        $result = app('rcloud')->getMessage()->System()->send($content);
//        $content = array(
//            'content'=>'test message',
//            'userInfo'=>[
//                'extra'=> [
//                    'un' => !empty($user->user_nick_name) ? $user->user_nick_name : ($user->user_name ?? ''),
//                    'ua' => userCover($user->user_avatar ?? ''),
//                    'ui' => $user->user_id,
//                    'ug' => $user->user_gender,
//                    'devicePlatformName' => 'server',
//                ]
//            ]
//        );
//        $content = \json_encode($content , JSON_UNESCAPED_UNICODE);
//        $result = app('rcloud')->getMessage()->Broadcast()->recall(array(
//            'senderId'   => 'system',
////            'targetId'   => $this->targetId,
//            "objectName" => "RC:SightMsg",
//            'content'    => \json_encode(array(
//                'sightUrl'=>1,
//                'content'=>1,
//                'duration'=>1,
//                'size'=>1,
//                'name'=>1,
//                'user'=>array(
//                    'id'=>1
//                ),
//                'extra'=>'extra'
//            ))
//,
//            'extra'=>array('user'=>1234567890),
//        ));
        Log::error($content);
        Log::error(\json_encode($result , JSON_UNESCAPED_UNICODE));
        return $this->response->created();
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

    public function call()
    {
        $keypair = new \Vonage\Client\Credentials\Keypair(
            file_get_contents('/home/www/.nexmo/private.key'),
            'b71f9142-11b1-4340-b502-f524244c20b9'
        );
        $client = new \Vonage\Client($keypair);

        $outboundCall = new \Vonage\Voice\OutboundCall(
            new \Vonage\Voice\Endpoint\Phone('6285817281840'),
            new \Vonage\Voice\Endpoint\Phone('8617600128988')
        );
        $ncco = new NCCO();
        $number = mt_rand(1111 , 9999);
        $talk = new \Vonage\Voice\NCCO\Action\Talk('Hi! Your verification code for lovbee is '.$number.'.');
        $ncco->addAction($talk->setLoop(2));
        $outboundCall->setNCCO($ncco);
        $response = $client->voice()->createOutboundCall($outboundCall);

        dump($response->getConversationUuid());
        dump($response->getDirection());
        dump($response->getFrom());
        dump($response->getStatus());
        dump($response->getTimestamp());
        dump($response->getTo());
        dump($response->getUuid());
        dump($response->getNetwork());
        dump($response->getRate());
        dump($response->getStartTime());
        dump($response->getEndTime());
        dump($response->getDuration());
        dump($response->getPrice());
        dump($number);
        dd($response);

    }


}
