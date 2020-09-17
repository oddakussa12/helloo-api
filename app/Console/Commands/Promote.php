<?php


namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Custom\PushServer\PushServer;
use Illuminate\Support\Facades\Redis;

class Promote extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promote:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'promote push';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->oneDay();
        $this->sevenDay();
        $this->thirtyDay();
    }

    private function testDay()
    {
        $userIds = array(

        );
        $languageRegistrationIds = array();
        if(!empty($userIds))
        {
            $userIds = trim(join(',' , $userIds) , ",");
            $sql = "SELECT * from (SELECT user_id,device_registration_id,device_language,device_register_type from f_devices where user_id in ({$userIds})  ORDER BY device_updated_at desc) a GROUP BY user_id";
            $devices = collect(\DB::select($sql))->map(function ($value) {
                return (array)$value;
            })->toArray();

            foreach ($devices as $device)
            {
                if($device['device_register_type']!='fcm')
                {
                    continue;
                }
                $language = $this->getSupportLanguage($device['device_language']);
                array_push($languageRegistrationIds , array('language'=>$language , 'registrationId'=>$device['device_registration_id']));
            }
        }
        $languageRegistrationIds = collect($languageRegistrationIds)->groupBy('language')->toArray();
        foreach ($languageRegistrationIds as $lang => $languageRegistrationId)
        {
            $languageRegistrationId = collect($languageRegistrationId)->chunk(50);
            foreach ($languageRegistrationId as $key=>$langRegistrationIds)
            {
                $registrationIds = $langRegistrationIds->pluck('registrationId')->toArray();
                $pushParam = $this->pushData($lang);
                $pushParam['registrationId'] = $registrationIds;
                (new PushServer($pushParam))->fcmPush();
            }
        }
    }

    private function oneDay()
    {
        $pushTime = $this->pushTime();
        $start = $pushTime['start'];
        $end = $pushTime['end'];
        $lastActivityTime = 'ry_user_last_activity_time';
        $options = array('withScores'=>true);
        $limit = 200;
        $count = Redis::zcount($lastActivityTime , $start , $end);
        $lastPage = ceil($count/$limit);
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
            $languageRegistrationIds = array();
            if(!empty($userIds))
            {
                $userIds = trim(join(',' , $userIds) , ",");
                $sql = "SELECT * from (SELECT user_id,device_registration_id,device_language,device_register_type from f_devices where user_id in ({$userIds})  ORDER BY device_updated_at desc) a GROUP BY user_id";
                $devices = collect(\DB::select($sql))->map(function ($value) {
                    return (array)$value;
                })->toArray();

                foreach ($devices as $device)
                {
                    if($device['device_register_type']!='fcm')
                    {
                        continue;
                    }
                    $language = $this->getSupportLanguage($device['device_language']);
                    array_push($languageRegistrationIds , array('language'=>$language , 'registrationId'=>$device['device_registration_id']));
                }
            }
            $languageRegistrationIds = collect($languageRegistrationIds)->groupBy('language')->toArray();
            foreach ($languageRegistrationIds as $lang => $languageRegistrationId)
            {
                $languageRegistrationId = collect($languageRegistrationId)->chunk(50);
                foreach ($languageRegistrationId as $key=>$langRegistrationIds)
                {
                    $registrationIds = $langRegistrationIds->pluck('registrationId')->toArray();
                    $pushParam = $this->pushData($lang);
                    $pushParam['registrationId'] = $registrationIds;
                    (new PushServer($pushParam))->fcmPush();
                }
            }
        }

    }

    private function sevenDay()
    {
        $pushTime = $this->pushTime(7);
        $start = $pushTime['start'];
        $end = $pushTime['end'];
        $lastActivityTime = 'ry_user_last_activity_time';
        $options = array('withScores'=>true);
        $limit = 200;
        $count = Redis::zcount($lastActivityTime , $start , $end);
        $lastPage = ceil($count/$limit);
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
            $languageRegistrationIds = array();
            if(!empty($userIds))
            {
                $userIds = trim(join(',' , $userIds) , ",");
                $sql = "SELECT * from (SELECT user_id,device_registration_id,device_language,device_register_type from f_devices where user_id in ({$userIds})  ORDER BY device_updated_at desc) a GROUP BY user_id";
                $devices = collect(\DB::select($sql))->map(function ($value) {
                    return (array)$value;
                })->toArray();

                foreach ($devices as $device)
                {
                    if($device['device_register_type']!='fcm')
                    {
                        continue;
                    }
                    $language = $this->getSupportLanguage($device['device_language']);
                    array_push($languageRegistrationIds , array('language'=>$language , 'registrationId'=>$device['device_registration_id']));
                }
            }
            $languageRegistrationIds = collect($languageRegistrationIds)->groupBy('language')->toArray();
            foreach ($languageRegistrationIds as $lang => $languageRegistrationId)
            {
                $languageRegistrationId = collect($languageRegistrationId)->chunk(50);
                foreach ($languageRegistrationId as $key=>$langRegistrationIds)
                {
                    $registrationIds = $langRegistrationIds->pluck('registrationId')->toArray();
                    $pushParam = $this->pushData($lang);
                    $pushParam['registrationId'] = $registrationIds;
                    (new PushServer($pushParam))->fcmPush();
                }
            }
        }

    }

    private function thirtyDay()
    {
        $pushTime = $this->pushTime(30);
        $start = $pushTime['start'];
        $end = $pushTime['end'];
        $lastActivityTime = 'ry_user_last_activity_time';
        $options = array('withScores'=>true);
        $limit = 200;
        $count = Redis::zcount($lastActivityTime , $start , $end);
        $lastPage = ceil($count/$limit);
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
            $languageRegistrationIds = array();
            if(!empty($userIds))
            {
                $userIds = trim(join(',' , $userIds) , ",");
                $sql = "SELECT * from (SELECT user_id,device_registration_id,device_language,device_register_type from f_devices where user_id in ({$userIds})  ORDER BY device_updated_at desc) a GROUP BY user_id";
                $devices = collect(\DB::select($sql))->map(function ($value) {
                    return (array)$value;
                })->toArray();

                foreach ($devices as $device)
                {
                    if($device['device_register_type']!='fcm')
                    {
                        continue;
                    }
                    $language = $this->getSupportLanguage($device['device_language']);
                    array_push($languageRegistrationIds , array('language'=>$language , 'registrationId'=>$device['device_registration_id']));
                }
            }
            $languageRegistrationIds = collect($languageRegistrationIds)->groupBy('language')->toArray();
            foreach ($languageRegistrationIds as $lang => $languageRegistrationId)
            {
                $languageRegistrationId = collect($languageRegistrationId)->chunk(50);
                foreach ($languageRegistrationId as $key=>$langRegistrationIds)
                {
                    $registrationIds = $langRegistrationIds->pluck('registrationId')->toArray();
                    $pushParam = $this->pushData($lang);
                    $pushParam['registrationId'] = $registrationIds;
                    (new PushServer($pushParam))->fcmPush();
                }
            }
        }

    }



    private function pushTime(int $day=1)
    {
        $start = Carbon::now()->subDays($day)->startOfDay()->timestamp;
        $end = Carbon::now()->subDays($day)->endOfDay()->timestamp;
        return compact('start' , 'end');
    }

    private function pushData($lang = 'en' , int $day = 1)
    {
        $title = $this->getTitle($day);
        return [
            'lang' =>$lang,
            'registerType'   => 'fcm',
            'title'          => $title,
            'extras'         => [
                'type'=>'recall',
                'title'=>$title
            ]
        ];
    }

    private function getTitle(int $day=1)
    {
        if($day<=1)
        {
            $message =  'You have not logged in for a day';
        }elseif ($day==7)
        {
            $message =  'You have not logged in for seven days';
        }elseif ($day==30)
        {
            $message =  'You have not logged in for seven days';
        }else{
            $message =  'You haven\'t logged in for a long time';
        }
        return $message;
    }

    public function getSupportLanguage($lang)
    {
        $locales = config('translatable.locales');
        $zhCNArray = array(
            'zh_hans_hk',
            'zh_hans_ca',
            'zh-hans-cn',
            'zh-hans-hk',
            'zh-hans-jp',
            'zh-hans-us',
            'zh-hans-vn',
            'zh-hans',
            'yue_hans',
            'yue_hans_cn',
            'zh-cn',
            'zh',
        );
        $zhTWArray = array(
            'zh_hant_cn',
            'zh_hant_tw',
            'zh_hant_hk',
            'zh_hant_mo',
            'zh-tw',
            'zh-hk',
            'zh-sg',
            'zh-hant',
            'zh-hant-cn',
            'zh-hant-hk',
            'zh-hant-tw',
            'zh-hant-mo',
        );
        if(in_array($lang ,$locales))
        {
            return $lang;
        }
        if(in_array(strtolower($lang) , $zhCNArray))
        {
            return 'zh-CN';
        }
        if(in_array(strtolower($lang) , $zhTWArray))
        {
            return 'zh-TW';
        }
        if(strstr('_' , $lang)!==false)
        {
            $languages = explode('_' , $lang);
            $language = $languages[0];
            if($language=='zh')
            {
                return 'zh-CN';
            }
            if(in_array($language ,$locales))
            {
                return $language;
            }
        }
        if(strstr('-' , $lang)!==false)
        {
            $languages = explode('-' , $lang);
            $language = $languages[0];
            if($language=='zh')
            {
                return 'zh-CN';
            }
            if(in_array($language ,$locales))
            {
                return $language;
            }
        }
        return 'en';
    }
}