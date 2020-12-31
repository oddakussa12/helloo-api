<?php

namespace App\Http\Controllers\V1;


use Aws\CognitoIdentity\CognitoIdentityClient;
use App\Custom\NEIm\NEMessage\MessageOptions;
use App\Custom\NEIm\NEMessage\NeTxtMessage;
use Aws\Credentials\CredentialProvider;
use Aws\DoctrineCacheAdapter;
use Doctrine\Common\Cache\ApcuCache;
use Godruoyi\Snowflake\Snowflake;
use App\Models\User;
use App\Custom\NEIm\NetEaseIm;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
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
use Illuminate\Support\Facades\DB;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;



class TestController extends BaseController
{
    public function __construct()
    {
        if(in_array(domain() , config('common.online_domain'))&&!app()->runningInConsole())
        {
            dd('Please use the test environment to test');
        }
    }

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
        if(domain()!=config('app.url'))
        {
            $nowFlake = new Snowflake();
            $uuid = $nowFlake->id();
            $nickName = substr($uuid , 0 , 8);
            $gender = mt_rand(0 , 1);
            $i = mt_rand(1 , 18);
            $avatar = "https://qnwebothersia.mmantou.cn/default_avatar_{$i}.png?imageView2/0/w/200/h/200/interlace/1|imageslim";
            $birth = Carbon::now()->subYears(mt_rand(10 , 20))->subMonths(mt_rand(1 , 12))->subDays(mt_rand(1 , 10))->toDateString();
            DB::table('temp_users')->insert(array(
                'user_uuid'=>$uuid,
                'user_gender'=>$gender,
                'user_nick_name'=>$nickName,
                'user_pwd'=>bcrypt(123456),
                'user_activation'=>1,
                'user_birthday'=>$birth,
                'user_avatar'=>$avatar,
                'user_created_at'=>Carbon::now()->toDateTimeString(),
                'user_activated_at'=>Carbon::now()->toDateTimeString(),
                'user_activated_at'=>Carbon::now()->toDateTimeString(),
            ));



            $account = app('netEase')->create_acc_id($uuid , $nickName , array(
                'icon'=>$avatar,
                'mobile'=>'+62-8'.mt_rand(1 , 9).mt_rand(1 , 9).mt_rand(1 , 9).mt_rand(1 , 9).mt_rand(1 , 9).mt_rand(1 , 9).mt_rand(1 , 9).mt_rand(1 , 9),
                'gender'=>$gender,
                'birth'=>$birth
            ));
//            $token = app('rcloud')->getUser()->register(array(
//                'id'=> time(),
//                'name'=> (new RandomStringGenerator())->generate(16),
//                'portrait'=> "https://qnwebothersia.mmantou.cn/default_avatar.jpg?imageView2/0/w/50/h/50/interlace/1|imageslim"
//            ));
            return $this->response->array($account->get_data());
        }
    }

    public function send()
    {
        $netEaseConfig = config('netease');
        $netEase = new NetEaseIm(array(
            'AppKey'=>$netEaseConfig['app_key'],
            'AppSecret'=>$netEaseConfig['app_secret']
        ));
        $option = new MessageOptions();
        $option = $option->setRoam(true)
                            ->setHistory(true)
                            ->setSenderSync(true)
                            ->setPush(true)
                            ->setRoute(false)
                            ->setBadge(true)
                            ->setNeedPushNick(true)
                            ->setPersistent(true);
        $txt = new NeTxtMessage($option);
        $txt = $txt->setFrom('181930645503606784')->setTo('182255020840845312')->setBody(array('msg'=>'hello'.millisecond()));
        $result = $netEase->message_send($txt , false , [] , [] , array('pushcontent'=>'hhahah' , 'push'=>true , 'payload'=>[]));


        dd($result);
    }

    public function group()
    {

    }

    public function es()
    {
        $params = [
            'index' => 'weather'
        ];
        $response = app('elastic-search')->indices()->create($params);
        dd($response);
    }

    public function aws()
    {
        $identity_pool_id = 'cn-north-1:db5a6388-b548-4e7b-af73-492c11fa7a2c';
//        $config = config('aws.cognito');
//        $cache = new DoctrineCacheAdapter(new ApcuCache);
//        $provider = CredentialProvider::defaultProvider();
//        $cachedProvider = CredentialProvider::cache($provider, $cache);
//        $config['credentials'] = $cachedProvider;
//        $aws = app('aws');
//        $identityTokenClient = $aws->createCognitoIdentity();
//        /* Acquire new Identity */
//        $identityToken = $identityTokenClient->getOpenIdTokenForDeveloperIdentity(array('IdentityPoolId' => $identity_pool_id, 'Logins' => array('login.helloo.com' => 'jkljka1sdjk')));
//        Log::info('IdentityToken' , $identityToken->toArray());
//        die;
//        $config = config('aws.Pinpoint');
//        $pinpointClient = $aws->createPinpoint($config);
//
//        try{
//            $pinpoint = $pinpointClient->phoneNumberValidate(array(
//                "NumberValidateRequest"=>array(
//                    "PhoneNumber"=>"17600128988",
//                    "IsoCountryCode"=>"86"
//                )
//            ));
//            Log::info('pinpoint' , $pinpoint->toArray());
//        }catch (\Exception $e)
//        {
//            Log::error($e->getMessage());
//        }



    }


}
