<?php

namespace App\Console\Commands;

use App\Custom\Uuid\RandomStringGenerator;
use App\Models\Business\Goods;
use App\Models\UserKpiCount;
use App\Models\User;
use App\Traits\CachableUser;
use App\Jobs\Test as TestJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Ramsey\Uuid\Uuid;
use App\Foundation\Auth\User\Update;
use App\Repositories\Contracts\UserRepository;
use App\Custom\EasySms\PhoneNumber;
use Illuminate\Support\Facades\Hash;


class Test extends Command
{
    use CachableUser,Update;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:test {type} {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto test';

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
        $this->fixBitrix();
        die;
        $schools = array(
            "Sekolah Menengah Atas Negri 10",
            "Addis Ababa University",
            "Colégio São Pedro",
            "ESTV - GTI/STM Becora",
            "Ensino Secundário 5 de Maio",
            "Ensino Secundário 28 de Novembro",
            "Nobel da Paz ",
            "Ensino Secundário 12 de Novembro",
            "Escola Técnica Informática ETI ",
            "Escola An-Nur",
            "Ensino Secundário Nicolao Lobato",
            "São José Operário",
            "Universidade Nacionál de Timor Lorosa'e",
        );
        $dates = array();
        $startDate = "2021-01-01";
        $endTime = "2021-03-14";
        do{
            array_push($dates , $startDate);
            $startDate = Carbon::createFromFormat('Y-m-d' , $startDate)->addDays(1)->toDateString();
        }while($startDate <= $endTime);
        foreach ($schools as $school)
        {
            foreach ($dates as $date)
            {
                $command = "chat:depth";
                $this->call($command , array('type'=>'school' , 'value'=>$school , 'date'=>$date));
            }
        }
    }

    public function fillSchool()
    {
        $file = storage_path('app/tmp/school.csv');
        foreach(file($file) as $line) {
            list($c , $s) = explode(',' , $line);
            $school = DB::table('schools')->where('name' , $s)->first();
            if(blank($school))
            {
                DB::table('schools')->insert(array(
                    'name'=>$s,
                    'country'=>$c,
                    'created_at'=>Carbon::now()->toDateTimeString()
                ));
            }
        }
    }

    public function sync()
    {
        DB::table('users')->where('user_created_at' , '<=' , '2021-02-16 08:44:13')->orderBy('user_created_at')->chunk(200 , function($users){
            $data = $users->pluck('user_activation' , 'user_id')->toArray();
            $time = $users->pluck('user_created_at' , 'user_id')->toArray();
            $userIds = array_keys($data);
            $userPhones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get();
            $table = array();
            foreach ($userPhones as $userPhone)
            {

                $country = '';
                $type = 0;
                if(blank($country)&&($userPhone->user_phone_country==1||$userPhone->user_phone_country==62))
                {
                    if(substr($userPhone->user_phone , 0 , 3)==473)
                    {
                        $type = 1;
                        $country = 'gd';
                    }
                }

                if(blank($country)&&(substr($userPhone->user_phone , 0 , 4)==1473||$userPhone->user_phone_country==473))
                {
                    $type = 1;
                    $country = 'gd';
                }

                if(blank($country)&&(substr($userPhone->user_phone , 0 , 1)==7&&strlen($userPhone->user_phone)==8))
                {
                    $type = 1;
                    $country = 'tl';
                }

                if(blank($country)&&$userPhone->user_phone_country==230)
                {
                    $type = 1;
                    $country = 'mu';
                }

                if(blank($country))
                {
                    $type = 0;
                    $country = $userPhone->user_phone_country;
                }
//                $createdAt = optional($time[$userPhone->user_id])->toDateTimeString();
                array_push($table , array(
                    'user_id'=>$userPhone->user_id,
                    'type'=>$type,
                    'country'=>$country,
                    'activation'=>$data[$userPhone->user_id],
                    'created_at'=>$time[$userPhone->user_id],
                ));
            }
            DB::table('users_countries')->insert($table);
        });
    }

    public function dau()
    {
//       $result = DB::select("SELECT
//	DISTINCT v_timor_users.user_id as id
//FROM
//	t_visit_logs_202102
//INNER JOIN v_timor_users ON t_visit_logs_202102.user_id = v_timor_users.user_id
//GROUP BY
//	DATE(
//		DATE_ADD(
//			FROM_UNIXTIME(
//				t_visit_logs_202102.visited_at
//			),
//			INTERVAL + 9 HOUR
//		)
//	);");
        $date = array(
            "2021-02-01",
            "2021-02-02",
            "2021-02-03",
            "2021-02-04",
            "2021-02-05",
            "2021-02-06",
            "2021-02-07",
            "2021-02-08",
            "2021-02-09",
            "2021-02-10",
            "2021-02-11",
            "2021-02-12",
            "2021-02-13",
            "2021-02-14",
            "2021-02-15",
        );
        foreach ($date as $d)
        {
            $result = DB::select('select DISTINCT v_timor_users.user_id as id from t_visit_logs_202102 INNER JOIN v_timor_users ON t_visit_logs_202102.user_id = v_timor_users.user_id where date(date_add(from_unixtime(t_visit_logs_202102.visited_at),INTERVAL + 9 HOUR))='."'$d'");
            $count = count($result);
            $data = array();
            foreach ($result as $r)
            {
                array_push($data , $r->id);
            }
            $userIds = trim(implode(',' , $data) , ',');
            $sql = "select chat_from_id,count(*) as c from t_ry_chats_202102 where chat_from_id in ($userIds) and chat_msg_type='Helloo:VideoMsg' and date(date_add(from_unixtime(floor(chat_time/1000)),INTERVAL + 9 HOUR))="."'$d' group by chat_from_id order by c desc";
            $c = DB::select($sql);
            $three = collect($c)->filter(function ($value, $key) {
                return $value->c >= 3;
            })->count();
            $two = collect($c)->filter(function ($value, $key) {
                return $value->c == 2;
            })->count();
            $one = collect($c)->filter(function ($value, $key) {
                return $value->c == 1;
            })->count();
            $zero = $count-$three-$two-$one;
            file_put_contents('count.csv' , $d.','.$count.','.(round($zero/$count,4)*100).'%,'.(round($one/$count,4)*100).'%,'.(round($two/$count,4)*100).'%,'.(round($three/$count,4)*100).'%'.PHP_EOL , FILE_APPEND);
        }
//        Carbon::createFromFormat('Y-m-d' , $date)->addDays(1)->toDateString();
//        dump($result);

//        DB::table('visit_logs_202102')->orderByDesc('id');
    }

    public function fixSchool()
    {
        $schools = DB::table('schools')->pluck('name' , 'key')->toArray();
        DB::table('users')->where('user_activation' , 1)->orderByDesc('user_created_at')->chunk(100 , function($users) use ($schools){
            foreach ($users as $user)
            {
                if(blank($user->user_sl)&&!blank($user->user_school))
                {
                    $school = $user->user_school;
                    if(isset($schools[$school]))
                    {
                        DB::table('users')->where('user_id' , $user->user_id)->update(array(
                            'user_sl'=>$schools[$school],
                        ));
                    }

                }
            }
        });
    }

    public function sms()
    {
        $file = storage_path('app/tmp/2.csv');
        $sms = app('easy-sms');

        $str = <<<DOC
NEW Lovbee Promotion!

Thank you joining our Lovbee Family. You have been selected to get $12 FREE data. All you need to do is add 3 friends to your Lovbee contacts ans you instantly win phone topup! It's that simple.

Free friends for you!
Add these contacts and start chatting now!
Sweets123
Septemberborn2005
kiannabain473
ramenandanime4L
Sheneal123
Queencess100

www.lovbee.fun
DOC;
        $i = 0;
        $f = 0;
        foreach(file($file) as $line) {
            list($country , $phone) = explode(',' , $line);
            $number = new PhoneNumber(trim($phone) , trim($country));
            try{
                $result = $sms->send($number, $str , array('aws'));
                Log::info('$result' , array($result));
                $i++;
            }catch (NoGatewayAvailableException $e)
            {
                $exception = $e->getLastException();
                Log::info('error' , array('$phone'=>$phone , 'message'=>$exception->getMessage()));
                $f++;
            }
            usleep(200);
        }
        echo PHP_EOL;
        echo $i;
        echo PHP_EOL;
        echo $f;
    }

    public function system()
    {
        $key = "helloo:account:system:senderId";
        $systemId = Redis::get($key);
        if($systemId===null)
        {
            return;
        }
        $sender = app(UserRepository::class)->findByUserId(intval($systemId))->toArray();
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
                'firstFrameUrl'=>'https://image.helloo.mantouhealth.com/other/20210204/6534e259a03675491654d58ce1c94969.png',
                'videoUrl'=>'https://video.helloo.mantouhealth.com/other/20210204/6534e259a03675491654d58ce1c94969.mp4',
            ),
            'pushContent'=>'video message',
            'pushExt'=>\json_encode(array(
                'title'=>'video message',
                'forceShowPushContent'=>1
            ))
        );
        dump($content);
        $this->sendSystem($content);
//        DB::table('users_phones')->orderByDesc('phone_id')->chunk(1000 , function($users) use ($content){
//            $userIds = collect($users)->pluck('user_id')->toArray();
//            $content['targetId'] = $userIds;
//            $this->sendSystem($content);
//        });
    }

    public function custom($talker)
    {
        $sender = collect(DB::table('users')->where('user_id' , $talker)->first())->toArray();
        if(blank($sender))
        {
            return;
        }
        $content = array(
            'content'=>'video message',
            'user'=> array(
                'id'=>$sender['user_id'],
                'name'=>$sender['user_nick_name'],
                'portrait'=>splitJointQnImageUrl($sender['user_avatar']),
                'extra'=>array(
                    'userLevel'=>$sender['user_level']
                ),
            ),
            'videoUrl'=>"http://video.helloo.mantouhealth.com/other/20210204/d0c6acc77db5a4cc5aceb31252f894c1.mp4",
            'firstFrameUrl'=>"https://image.helloo.mantouhealth.com/other/20210204/d0c6acc77db5a4cc5aceb31252f894c1.png",
            'videoPath'=>'',
            'firstFramePath'=>'',
        );
        $content = array(
            'senderId'   => $talker,
            "objectName" => "Helloo:VideoMsg",
            'content'    => \json_encode($content),
            'pushContent'=>'video message',
            'pushExt'=>\json_encode(array(
                'title'=>'video message',
                'forceShowPushContent'=>1
            ))
        );
        DB::table('signup_infos')->whereIn('signup_isocode' , array('au' , 'tl'))->orderByDesc('signup_id')->chunk(100 , function($users) use ($talker , $content){
            $userIds = collect($users)->pluck('user_id')->toArray();
            $userIds = array_diff($userIds , array($talker));
            $content['targetId'] = $userIds;
            $this->sendSystemPerson($content);
        });
    }

    public function person()
    {
        $talks = DB::table('escort_talker')->select('user_id')->distinct()->get()->toArray();
        foreach ($talks as $talk)
        {
            $talk = collect($talk)->toArray();
            $sender = collect(DB::table('users')->where('user_id' , $talk['user_id'])->first())->toArray();
            if(blank($sender))
            {
                Log::info('empty_$sender' , array($talk['user_id']));
                continue;
            }
            $content = array(
                'content'=>'video message',
                'user'=> array(
                    'id'=>$sender['user_id'],
                    'name'=>$sender['user_nick_name'],
                    'portrait'=>splitJointQnImageUrl($sender['user_avatar']),
                    'extra'=>array(
                        'userLevel'=>$sender['user_level']
                    ),
                ),
                'videoUrl'=>"https://video.helloo.mantouhealth.com/other/20210129/20210129112004.mp4",
                'firstFrameUrl'=>"https://image.helloo.mantouhealth.com/other/20210129/20210129112004.png",
                'videoPath'=>'',
                'firstFramePath'=>'',
            );
            $content = array(
                'senderId'   => $talk['user_id'],
//                'targetId'   => $targetId,
                "objectName" => "Helloo:VideoMsg",
                'content'    => \json_encode($content),
                'pushContent'=>'video message',
                'pushExt'=>\json_encode(array(
                    'title'=>'video message',
                    'forceShowPushContent'=>1
                ))
            );

            DB::table('users_friends')->where('user_id' , $talk['user_id'])->orderByDesc('friend_id')->chunk(10 , function($friends) use ($content){
                foreach ($friends as $friend)
                {
                    $content['targetId'] = $friend->friend_id;
                    Log::info('$friend->friend_id' , array($friend->friend_id));
                    $this->sendPerson($content);
                }
            });
        }
    }

    public function sendPerson($content)
    {
        Log::info('sendPerson_content' , $content);
        $result = app('rcloud')->getMessage()->Person()->send($content);
        Log::info('sendPerson_result' , $result);
    }

    public function sendSystem($content)
    {
        Log::info('sendSystem_content' , $content);
        $result = app('rcloud')->getMessage()->System()->broadcast($content);
        Log::info('sendSystem_result' , $result);
    }

    public function sendSystemPerson($content)
    {
        sleep(10);
        Log::info('sendSystemPerson_content' , $content);
        $result = app('rcloud')->getMessage()->System()->send($content);
        Log::info('sendSystemPerson_result' , $result);
    }
    public function fixIso()
    {
        DB::table('signin_infos')->where('isocode' , 'US_DEFAULT')->orderByDesc('id')->chunk(1000 , function ($users){
            foreach ($users as $user)
            {
                $sign_in_info = array();
                $geo = geoip($user->ip);
                $iso_code = strval($geo->iso_code);
                if($iso_code!='US_DEFAULT')
                {
                    $sign_in_info['isocode'] = $iso_code;
                    $sign_in_info['country'] = strval($geo->country);
                    $sign_in_info['state'] = strval($geo->state_name);
                    $sign_in_info['city'] = strval($geo->city);
                    $sign_in_info['lat'] = strval($geo->lat);
                    $sign_in_info['lon'] = strval($geo->lon);
                    $sign_in_info['timezone'] = strval($geo->timezone);
                    $sign_in_info['continent'] = strval($geo->continent);
                    DB::table('signin_infos')->where('id' , $user->id)->update($sign_in_info);
                }
            }
        });
        DB::table('signup_infos')->where('signup_isocode' , 'US_DEFAULT')->orderByDesc('signup_id')->chunk(1000 , function ($users){
            foreach ($users as $user)
            {
                $signup_info = array();
                $geo = geoip($user->signup_ip);
                $iso_code = strval($geo->iso_code);
                if($iso_code!='US_DEFAULT')
                {
                    $signup_info['signup_isocode'] = strval($geo->iso_code);
                    $signup_info['signup_country'] = strval($geo->country);
                    $signup_info['signup_state'] = strval($geo->state_name);
                    $signup_info['signup_city'] = strval($geo->city);
                    $signup_info['signup_lat'] = strval($geo->lat);
                    $signup_info['signup_lon'] = strval($geo->lon);
                    $signup_info['signup_timezone'] = strval($geo->timezone);
                    $signup_info['signup_continent'] = strval($geo->continent);
                    DB::table('signup_infos')->where('signup_id' , $user->signup_id)->update($signup_info);
                }
            }
        });
    }

    public function deleteCache()
    {
        DB::table('users')->orderByDesc('user_id')->chunk(1000 , function ($users){
            foreach ($users as $user)
            {
                $key = "helloo:account:service:account:".$user->user_id;
                Redis::del($key);
                Redis::del('helloo:account:service:account-personal-privacy:'.$user->user_id);
            }
        });
    }

    public function fixOrder()
    {
        DB::table('orders')->orderByDesc('created_at')->chunk(100 , function ($orders){
            foreach ($orders as $order)
            {
                $promo_price = $order->order_price;
                $total_price = $order->discounted_price-$order->delivery_coast;
                DB::table('orders')->where('order_id' , $order->order_id)->update(array(
                    'promo_price'=>$promo_price,
                    'total_price'=>round($total_price , 2),
                ));
            }
        });
        DB::table('users')->orderByDesc('user_id')->chunk(1000 , function ($users){
            $userIds = $users->pluck('user_id')->toArray();
            $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get();
            foreach ($users as $user)
            {
                $phone = $phones->where('user_id' , $user->user_id)->first();
                DB::table('users')->where('user_id' , $user->user_id)->update(
                    array(
                        'user_currency'=>empty($phone)||$phone->user_phone_country!='251'?'USD':'BIRR'
                    )
                );
            }
        });
    }

    public function syncBitrix()
    {
        $bx24 = app("bitrix24");

        DB::table('users')->where('user_shop' , 1)->where('user_verified' , 1)->orderByDesc('user_created_at')->chunk(50 , function($shops) use ($bx24){
            foreach ($shops as $shop)
            {
                $phone = DB::table('users_phones')->where('user_id' , $shop->user_id)->first();
                $id = $bx24->addCompany(array(
                    "TITLE"=>$shop->user_name,
                    "COMPANY_TYPE"=>"CLIENT",
                    "INDUSTRY"=>"OTHER",
                    "CURRENCY_ID"=>"ETB",
                    "PHONE"=>array(
                        array(
                            "VALUE"=>empty($phone)?'':$phone->user_phone_country.$phone->user_phone, "VALUE_TYPE"=>"WORK"
                        )
                    ),
                    "ADDRESS"=>$shop->user_address,
                    "UF_CRM_1629181078"=>$shop->user_nick_name
                ));
                $sectionId = $bx24->request('crm.productsection.add' , array(
                    'fields'=>array(
                        'CATALOG_ID'=>0,
                        'NAME'=>$shop->user_nick_name,
                        'XML_ID'=>$shop->user_id,
                    )
                ));
                DB::table('bitrix_shops')->insert(array(
                    'user_id'=>$shop->user_id,
                    'extension_id'=>$id,
                    'section_id'=>$sectionId,
                ));
            }
        });
        DB::table('goods')->orderByDesc('created_at')->chunk(50 , function ($goods) use ($bx24){
            foreach ($goods as $g)
            {
                $section = DB::table('bitrix_shops')->where('user_id' , $g->user_id)->first();
                if(!empty($section))
                {
                    $id = $bx24->addProduct(array(
                        "NAME"=>$g->name,
                        "PRICE"=>$g->price,
//                    "CURRENCY_ID"=>$g->currency,
                        "XML_ID"=>$g->id,
                        "SECTION_ID"=>$section->section_id??0
                    ));
                    DB::table('goods')->where('id' , $g->id)->update(array(
                        'extension_id'=>$id
                    ));
                }else{
                    dump($g);
                }

            }
        });

    }

    public function fixBitrix()
    {
        $bx24 = app("bitrix24");
        $results = $bx24->getProductList();
        foreach ($results as $products)
        {
            $gIds = collect($products)->pluck('XML_ID')->toArray();
            $gs = Goods::whereIn('id' , $gIds)->get();
            foreach ($products as $product) {
                $gId = $product['XML_ID'];
                $g = $gs->where('id' , $gId)->first();
                if(empty($g))
                {
                    Log::info('bitrix_1' , $product);
                    continue;
                }
                $bitrix = DB::table('bitrix_shops')->where('user_id' , $g->user_id)->first();
                if(empty($bitrix))
                {
                    Log::info('bitrix_2' , $product);
                    continue;
                }
                $data['PRICE'] = $g->price;
                $data['NAME'] = $g->name;
                $data['SECTION_ID'] = $bitrix->section_id;
                $bx24->updateProduct($product['ID'] , $data);
            }
        }
    }
}
