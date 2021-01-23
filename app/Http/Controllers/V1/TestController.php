<?php

namespace App\Http\Controllers\V1;


use App\Jobs\SignUpAndEvent;
use App\Jobs\EscortTalk;
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
use App\Repositories\Contracts\EventRepository;
use App\Custom\FireBase\Message\OptionsBuilder;
use App\Custom\FireBase\Message\PayloadDataBuilder;
use App\Custom\FireBase\Message\PayloadNotificationBuilder;
use FCM;




class TestController extends BaseController
{
    public function __construct()
    {
        Log::info('ip' , array(getRequestIpAddress()));
        if(in_array(domain() , config('common.online_domain'))&&!app()->runningInConsole()&&getRequestIpAddress()!="121.69.10.82")
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
        $now = Carbon::now()->timestamp;
        $id = intval(request()->input('id' , 0));
        if($id>0)
        {
            $activeEvents = collect(app(EventRepository::class)->getByAttributes(array('id'=>$id)))->toArray();
        }else{
            $activeEvents = app(EventRepository::class)->getActiveEvent();
        }
        if(!blank($activeEvents))
        {
            $key = "helloo:account:system:senderId";
            $systemId = Redis::get($key);
            $sender = app(UserRepository::class)->findByUserId($systemId)->toArray();
            $content = array(
                'senderId'   => $sender['user_id'],
                "objectName" => "Helloo:VideoMsg",
                'content'    => array(
                    'content'=>'video message',
                    'user'=> array(
                        'id'=>$sender['user_id'],
                        'name'=>$sender['user_nick_name'],
                        'portrait'=>$sender['user_avatar_link'],
                        'extra'=>array(
                            'userLevel'=>$sender['user_level']
                        ),
                    ),
                    'videoPath'=>'',
                    'firstFramePath'=>'',
//                    'firstFrameUrl'=>'https://qnidyooulimage.mmantou.cn/FisdVkCRfoLDT3bOCfi9XLX8XWpu.png?imageView2/5/w/192/h/192/interlace/1|imageslim',
//                    'videoUrl'=>'https://test.video.helloo.mantouhealth.com/38af86134b65d0f10fe33d30dd76442e/20210107/t.mp4',
                ),
                'pushContent'=>'video message',
                'pushExt'=>\json_encode(array(
                    'title'=>'video message',
                    'forceShowPushContent'=>1
                ))
            );
            foreach ($activeEvents as $activeEvent)
            {
                if($activeEvent['ended_at']<$now)
                {
                    continue;
                }
                $content['content']['firstFrameUrl'] = $activeEvent['image'];
                $content['content']['videoUrl'] = $activeEvent['value'];
                $this->sendEventMessage($content);
            }
        }

    }

    private function sendEventMessage($content)
    {
        Log::info('$content' , $content);
        $result = app('rcloud')->getMessage()->System()->broadcast($content);
        Log::info('$result' , $result);
    }

    public function push(Request $request)
    {
        $userId = intval($request->input('userId' , 0));
        $time = date('Y-m-d H:i:s');
        $content = array(
            'platform'=>array('Android'),
            'audience'=>array(
                'userid'=>array(
                    $userId
                ),
                'is_to_all'=>false
            ),
            'notification'=>array(
                'alert'=>'test alert'.$time,
                'android'=>array(
                    'alert'=>'over write alert'.$time,
                    'extras'=>array('test'=>1),
                    'badge'=>1
                ),
            )
        );
        Log::info('ry_push_content' , $content);
        $result = app('rcloud')->getPush()->push($content);
        Log::info('ry_push_result' , $result);
        return $this->response->created();
    }


    public function token()
    {
        Log::info('token_all' , request()->all());
        $userId = intval(request()->input('user_id'));
        $token = strval(request()->input('token'));
        DB::table('fcm_tokens')->insert(array(
            'user_id'=>$userId,
            'token'=>$token,
            'created_at'=>Carbon::now()->toDateTimeString(),
        ));
        return $this->response->noContent();
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
            'index' => 'post_translation',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 0
                ],
                'mappings' => [
                    '_source' => [
                        'enabled' => true
                    ],
                    'properties' => [
                        'post_uuid' => [
                            'type' => 'keyword',
//                            'index'=>false
                        ],
                        'post_content' => [
                            'type' => 'text',
                            'analyzer' => 'icu_analyzer'
                        ],
//                        'topics' => [
//                            'type' => 'keyword',
//                        ]
                    ]
                ]

            ]
        ];
        $response = app('elastic-search')->indices()->create($params);
        dd($response);
    }
    /**
     * $params['index']       = (string) Default index for items which don't provide one
     *        ['type']        = (string) Default document type for items which don't provide one
     *        ['consistency'] = (enum) Explicit write consistency setting for the operation
     *        ['refresh']     = (boolean) Refresh the index after performing the operation
     *        ['replication'] = (enum) Explicitly set the replication type
     *        ['fields']      = (list) Default comma-separated list of fields to return in the response for updates
     *        ['body']        = (array) The document
     **/
    public function esDoc()
    {
        $params = array(
            'index'=>'post_translation',
//            'type'=>'_doc',
            'body'=>[
                'query'=>[
                    'constant_score'=>array(
                        'filter'=>array(
                            'terms'=>array(
                                'topics'=>array('php')
                            )
                        )
                    ),

                ]
            ]
        );
        $response = app('elastic-search')->search($params);
        dd($response);
        die;
        $contents = array(
            "林书豪的下家终于确定了！北京时间1月5日，据国内媒体报道，林书豪在近期与首钢沟通时表示：如果可以，他愿意在CBA第二阶段结束后加盟北京首钢，重返CBA！",
            "第二阶段的CBA赛事将在2月4日结束，也就是说，我们很快就能看到书豪再次站在赛场上。兜兜转转，豪哥还是回到了首钢。这也意味着他重返NBA的旅途暂告一段落",
            "这次书豪冲击NBA的过程称得上是一波三折。在2019-20赛季结束后，他毅然选择离开CBA，当时就传出不少NBA球队对他有意。甚至有国内媒体表示，库里明确希望书豪能够加盟勇士。",
            "不过随着休赛期的结束，并没有哪支球队有实际的签约动作。豪哥看这条路行不通，转而在发展联盟寻找机会。他首先加入了发展联盟选拔队，并在此打了一场正式比赛，可选拔队的主要目的是培养年轻人，如今33岁的豪哥得不到多少上场时间。",
            "于是他选择离开选拔队，此后勇士抛来了橄榄枝，想给林书豪一份Exhibit 10合同。球队希望签约之后再把他裁掉，让豪哥去圣克鲁兹勇士征战发展联盟。这样一来，金州勇士就有了豪哥的优先权，可以随时召他入队。",
            "但根据NBA规定，这项操作必须在球队阵容名单截止前完成，也就是2020年12月20日中午12点整。由于豪哥未能及时取得FIBA的澄清信，勇士也错过了签约的最后期限。",
            "天无绝人之路，NBA在去年12月25日取消了对发展联盟球队签约老将自由球员的限制。每支球队可以指定一名在NBA效力5年及以上的老将加入阵容。又有记者曝出，勇士仍旧保持着对书豪的兴趣，希望他能来下属发展联盟球队效力。",
            "据专家据Jonathan Givony统计，目前自由市场上，有超过100名老将符合条件，却没有跟NBA球队签约。其中包括杰拉德-格林、卡罗尔、科沃尔、小托马斯、穆迪埃、克劳福德、香波特等。竞争同样十分激烈。",
            "如今距离这一规定颁布已经过去了10天，勇士依旧没有动作。但大外援受伤的首钢却一直在和书豪积极沟通，等待着他能够回心转意。",
            "本赛季没有了书豪的首钢艰难无比，汉密尔顿受伤、新帅被换，战绩也很不理想，目前以10胜14负的战绩排在联盟第14。上赛季书豪在时，他场均能贡献22.3分5.7板5.6助1.8断，带队夺得常规赛第4，差别可见一斑。",
            "但书豪想回归也并非那么容易。据欧媒Sportando爆料，首钢即将签约前NBA球员乔丹-麦克雷。麦克雷身高1米96，司职后卫，上赛季效力过奇才、掘金和活塞。场均能贡献6.9分1.8板1.4助，命中率41.7%，得分能力极强。",
            "希望早日在CBA赛场上看到豪哥的身影。多少人和教主想法一致？右下角在看集合吧！",
        );
        $body = array();
        $tags = array(
            'java',
            'php',
            'go',
            'ruby',
            'perl',
            'c',
            'c++',
            'javascript',
            'swift',
            'dart',
            'rust',
            'kotlin',
            'bush',
            'awk',
            'sql',
            'python',
            'c#',
            'vb',
            'lua',
            'scheme',
        );
        foreach ($contents as $content)
        {

            $uuid = Uuid::uuid1()->toString();
            $indexes = array_rand($tags , 2);
            $tag = array($tags[$indexes[0]] , $tags[$indexes[1]]);
            array_push($body , array(
                'index'=>array(
                    '_index' => 'post_translation',
//                    '_type' => '_doc',
                )
            ) , array(
                'post_uuid'=>$uuid,
                'post_content'=>$content,
                'topics'=>$tag,
            ));
        }
        $params = [
//            'index' => 'post_translation',
//            'type' => '_doc',
//            'fields'=>
            'body'=>$body
        ];
        dump($params);
        $response = app('elastic-search')->bulk($params);
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

    public function office()
    {
        $t = request()->input('t' , 'n');
        $userId = intval(request()->input('user_id' , '219'));
        if($t=='n')
        {
            $sender = app(UserRepository::class)->findOrFail($userId);
            $this->dispatchNow((new EscortTalk($sender , array('user_phone_country'=>62))));
        }else{
            $sender = app(UserRepository::class)->findOrFail($userId);
            $this->dispatchNow((new SignUpAndEvent($sender)));
        }
    }

    public function log(Request $request)
    {
        $all = $request->all();
        DB::table('logs')->insert(array('log'=>\json_encode($all , JSON_UNESCAPED_UNICODE) , 'created_at'=>Carbon::now()->toDateTimeString()));
        return $this->response->created();
    }

    public function fcm(Request $request)
    {
//        if(!in_array(domain() , config('common.online_domain')))
//        {
//            $userId  = intval($request->input('userId'));
//            $token = DB::table('fcm_tokens')->where('user_id' , $userId)->first();
//            if(!blank($token))
//            {
//                return $this->response->array(array('result'=>"https://api.helloo.mantouhealth.com/api/test/fcm?tokens[]=".$token->token));
//            }
//            die;
//        }
        $tokens  = (array)$request->input('tokens');
        $tokens = array_filter($tokens , function($value){
            return !blank($value);
        });
        if(!blank($tokens))
        {
            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60*20);

            $notificationBuilder = new PayloadNotificationBuilder('my title');
            $notificationBuilder->setBody('Hello world')
                ->setSound('default');

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData(['a_data' => 'my_data']);

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();
            $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);
            Log::info('fcm' , array($downstreamResponse->numberSuccess() , $downstreamResponse->numberFailure() , $downstreamResponse->numberModification()));
        }
        return $this->response->noContent();

    }


}
