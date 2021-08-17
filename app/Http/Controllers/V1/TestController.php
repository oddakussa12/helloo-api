<?php

namespace App\Http\Controllers\V1;


use App\Custom\DingNotice\DingTalk;
use App\Jobs\EasySms;
use App\Jobs\SignUpAndEvent;
use App\Jobs\EscortTalk;
use App\Messages\ForgetPasswordMessage;
use Aws\CognitoIdentity\CognitoIdentityClient;
use App\Custom\NEIm\NEMessage\MessageOptions;
use App\Custom\NEIm\NEMessage\NeTxtMessage;
use Aws\Credentials\CredentialProvider;
use Aws\DoctrineCacheAdapter;
use App\Custom\Doctrine\Common\Cache\ApcuCache;
use Godruoyi\Snowflake\Snowflake;
use App\Models\User;
use App\Custom\NEIm\NetEaseIm;
use Carbon\Carbon;
use libphonenumber\PhoneNumberUtil;
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
use Vonage\Client\Credentials\Basic;
use Vonage\Client;
use Vonage\SMS\Message\SMS;




class TestController extends BaseController
{
    public function __construct()
    {
        if(in_array(domain() , config('common.online_domain'))&&!app()->runningInConsole()&&getRequestIpAddress()!="121.69.10.82")
        {
            dd('Please use the test environment to test');
        }
    }

    public function index()
    {
        $sms = app('easy-sms');
        $number = new PhoneNumber(4734190916 , 1);
        $str = <<<DOC
Promosaun Lovbee!
 
Obrigada barak ba uza ona Lovbee. Ita bo'ot iha ona kolega 1 iha Lovbee, adisiona kolega 2 tan iha Lovbee no manan pulsa $1, sei transfere diretamente ba ita nia numero telemovel!
Bele adisiona diretamente kolega sira ne’e iha Lovbee no hetan pulsa $1.
- ID: NongYo06, 
- ID: Linda07,
- ID: Desy02,
- ID: Thavya30, 
- ID: morrales31.
DOC;

        try{
            $result = $sms->send($number, $str , array('aws'));
            Log::info('$result' , array($result));
        }catch (NoGatewayAvailableException $e)
        {
            $exception = $e->getLastException();
            Log::info('error' , array($exception->getMessage()));
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


    public function test()
    {
        Log::info('all' , request()->all());
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
            $target = app(UserRepository::class)->findOrFail($userId);
            $this->dispatchNow((new EscortTalk($target , array('user_phone_country'=>62))));
        }else{
            $target = app(UserRepository::class)->findOrFail($userId);
            $this->dispatchNow((new SignUpAndEvent($target)));
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

    public function ding()
    {
        $result = app(DingTalk::class)->with('default')->text('hi,lovbee!');
        Log::info('ding' , array($result));
    }

    public function sms()
    {
//        $phone = new PhoneNumber('76189946' , '670');
//        $message = new ForgetPasswordMessage(mt_rand(1111 , 9999));
//        $this->dispatchNow(new EasySms($phone , $message));
//        die;
        $phones = array(
            '14734207871',
            '14735363744',
            '14734102020',
            '14734172381',
            '14735371021',
            '14734587454',
            '14734190916',
            '14734215614',
            '14734599102',
            '14734493520',
            '14734181125',
            '14734030264',
            '14734572539',
            '14734592592',
            '14734587775',
            '14734239702',
            '14734186662',
            '14734228345',
            '14735376815',
            '14734062635',
            '14734494295',
            '14734211362'
        );
        $basic  = new Basic('0a271b0f', 'DROFebmLfoHe6aVr');
        $client = new Client($basic);
        $fail = array();
        foreach ($phones as $phone)
        {
            $message = $client->message()->send([
                'to' => $phone,
                'from' => 'Vonage APIs',
                'text' => sprintf('Your Lovbee APP security code is %s. Yours is logging in Lovbee!. Please keep your account information in a safe place.', mt_rand(1111 , 9999))
            ]);
            Log::info('$message' , array($message));
            if($message->getResponse()->getStatusCode()!=200)
            {
                array_push($fail , $phone);
            }
            usleep(500);
        }
        dump('=======================================================');
        dump($fail);

    }


}
