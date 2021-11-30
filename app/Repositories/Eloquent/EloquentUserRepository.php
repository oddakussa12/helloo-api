<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;


use Carbon\Carbon;
use App\Jobs\School;
use App\Jobs\RyOnline;
use App\Jobs\UserUpdate;
use App\Custom\RedisList;
use App\Models\BlockUser;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\OneTimeUserScoreUpdate;
use App\Events\UserProfileLikeEvent;
use Illuminate\Support\Facades\Redis;
use App\Events\UserProfileRevokeLikeEvent;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;

class EloquentUserRepository  extends EloquentBaseRepository implements UserRepository
{
    /**
     * @note 用户更新
     * @datetime 2021-07-12 19:27
     * @param $model
     * @param array $data
     * @return mixed
     */
    public function update($model, $data)
    {
        $original = collect($model)->toArray();
        $user     = parent::update($model, $data);
        $key      = "helloo:account:service:account:".$model->getKey();

        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        $ageSortSetKey    = 'helloo:account:service:account-age-sort-set';
        Redis::del($key);
        isset($data['user_gender'])&&Redis::zadd($genderSortSetKey , intval($data['user_gender']) , $model->getKey());
        isset($data['user_birthday'])&&Redis::zadd($ageSortSetKey , age($data['user_birthday']) , $model->getKey());
        if(isset($data['user_avatar'])||isset($data['user_nick_name']))
        {
//            UserUpdate::dispatch($user)->onQueue('helloo_{user_update}');
        }
        $now = Carbon::now()->toDateTimeString();
        if(isset($data['user_sl'])||isset($data['user_school']))
        {
            if(isset($data['user_sl']))
            {
                $school = $data['user_sl'];
            }else{
                $school = DB::table('schools')->where('key' , $data['user_school'])->first();
                if(blank($school))
                {
                    $school = '';
                }else{
                    $school = $school->name;
                }
            }
            if(!blank($school))
            {
                $logData = array(
                    'id'=>app('snowflake')->id(),
                    'user_id'=>$model->getKey(),
                    'school'=>$school,
                    'created_at'=>$now,
                );
                DB::table('users_schools_logs')->insert($logData);
                School::dispatch($school)->onQueue('helloo_{user_school}');
//                if (empty($original['user_sl']) || $original['user_sl'] == 'other') {
//                    OneTimeUserScoreUpdate::dispatch($user , 'fillSchool')->onQueue('helloo_{one_time_user_score_update}');
//                }
//                if($original['user_sl']=='other')
//                {
//                    OneTimeUserScoreUpdate::dispatch($user , 'fillSchoolOther')->onQueue('helloo_{one_time_user_score_update}');
//                }
            }
        }
//        if(isset($data['user_avatar'])&& stripos($original['user_avatar_link'], 'default_avatar')!==false)
//        {
//            OneTimeUserScoreUpdate::dispatch($user , 'fillAvatar')->onQueue('helloo_{one_time_user_score_update}');
//        }
//        if(isset($data['user_bg'])&&blank($original['user_bg']))
//        {
//            OneTimeUserScoreUpdate::dispatch($user , 'fillCover')->onQueue('helloo_{one_time_user_score_update}');
//        }
//        if(isset($data['user_about'])&& blank($original['user_about']))
//        {
//            OneTimeUserScoreUpdate::dispatch($user , 'fillAbout')->onQueue('helloo_{one_time_user_score_update}');
//        }
        if(isset($data['user_name']))
        {
            $key = 'helloo:account:service:account-username-change';
//            $changed = Redis::zscore($key , $user->user_id);
            $index = ($user->user_id)%2;
            $usernameKey = 'helloo:account:service:account-username-'.$index;
            try{
                DB::beginTransaction();
                $nameLogResult = DB::table('users_names_logs')->insert(array(
                    'user_id'=>$user->user_id,
                    'user_name'=>$original['user_name'],
                    'created_at'=>$now,
                ));
                if(!$nameLogResult)
                {
                    abort(405 , 'user name log insert failed!');
                }
                DB::commit();
                Redis::sadd($usernameKey , strtolower($data['user_name']));
                Redis::zadd($key , strtotime($now) , $user->user_id);
//                ($changed===null||$changed===false) && OneTimeUserScoreUpdate::dispatch($user , 'fillName')->onQueue('helloo_{one_time_user_score_update}');
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('username_update_fail' , array(
                    'message'=>$e->getMessage(),
                    'user_id'=>$model->getKey(),
                    'user_name'=>$original['user_name'],
                    'username'=>$data['user_name']
                ));
            }
        }
        return $user;
    }

    /**
     * @note 用户新增
     * @datetime 2021-07-12 19:27
     * @param $data
     * @return mixed
     */
    public function store($data)
    {
        return $this->model->create($data);
    }

    /**
     * @note 获取隐私
     * @datetime 2021-07-12 19:27
     * @param $userId
     * @return array|int[]|mixed
     */
    public function findPrivacyByUserId($userId)
    {
        $key = 'helloo:account:service:account-personal-privacy:'.$userId;
        $cache = Redis::get($key);
        if(!empty($cache))
        {
            $privacy = json_decode($cache, true);
        }else{
            $userPrivacy = collect(DB::table('users_settings')->where('user_id' , $userId)->first());
            if(!blank($userPrivacy))
            {
                $privacy = $userPrivacy->only('friend' , 'video' , 'photo' , 'shop')->toArray();
            }else{
                $privacy = ['friend'=>1, 'video'=>1, 'photo'=>1, 'shop'=>1];
            }
            Redis::set($key , \json_encode($privacy));
            Redis::expire($key , 60*60*24);
        }
        return $privacy;
    }

    /**
     * @note 批量获取用户信息(cache)
     * @datetime 2021-07-12 19:27
     * @param $userIds
     * @return \Illuminate\Support\Collection
     */
    public function findByUserIds($userIds)
    {
        return collect($userIds)->transform(function($userId , $key){
            return $this->findByUserId($userId);
        });
    }

    /**
     * @note 获取用户信息(cache)
     * @datetime 2021-07-12 19:28
     * @param $userId
     * @return \Illuminate\Support\Collection
     */
    public function findByUserId($userId)
    {
        $key = "helloo:account:service:account:".$userId;
        $user = Redis::hgetall($key);
        if(blank($user))
        {
            $user = collect($this->model->find($userId));
            if(!blank($user))
            {
                $cache = collect($user)->toArray();
                Redis::hmset($key , $cache);
                Redis::expire($key , 60*60*24);
            }
        }else{
            $user = collect($user);
        }
        return $user;
    }

    /**
     * @note 批量获取用户Tag
     * @datetime 2021-07-12 19:28
     * @param $userIds
     * @return \Illuminate\Support\Collection
     */
    public function findTagByUserIds($userIds)
    {
        return collect($userIds)->map(function($userId , $key){
            $tag = $this->findTagByUserId($userId);
            if($tag==='')
            {
                $color = '';
            }else{
                $pos = strripos($tag , "|");
                $pos = $pos===false?strlen($tag):$pos;
                $color = substr($tag , $pos+1);
                $tag = substr($tag , 0 , $pos);
            }
            return collect(['user_id'=>$userId , 'tag'=>strval($tag) , 'color'=>strval($color)]);
        });
    }

    /**
     * @note 获取用户Tag
     * @datetime 2021-07-12 19:28
     * @param $userId
     * @return string
     */
    public function findTagByUserId($userId)
    {
        $key = "helloo:account:service:tag:account:".$userId;
        $tag = Redis::get($key);
        if($tag===null)
        {
            $tagModel = DB::table('users_game_tags')->where('user_id' , $userId)->first();
            if(!blank($tagModel))
            {
                $tag = $tagModel->tag."|".$tagModel->color;
            }else{
                $tag = '';
            }
            Redis::set($key , $tag);
            Redis::expire($key , 60*60*24);
        }
        return $tag;
    }

    /**
     * @note 获取用户信息
     * @datetime 2021-07-12 19:28
     * @param $userId
     * @return mixed
     */
    public function findOrFail($userId)
    {
        return $this->model->findOrFail($userId);
    }

    public function unblockUser($userId)
    {
        if (auth()->check()) {
            $authUser = auth()->id();
            if ($authUser != $userId) {
                $this->removeHiddenUsers($authUser, $userId);
            }
        }
    }

    public function removeHiddenUsers($id, $user_id)
    {
        $userHiddenUsersKey = $this->hiddenUsersMemKey($id);
        if (Redis::sismember($userHiddenUsersKey, $user_id)) {
            Redis::srem($userHiddenUsersKey, $user_id);
            BlockUser::where('user_id', $id)->where('blocked_user_id', $user_id)->update(
                array('is_deleted' => 1)
            );
        }
    }

    public function blockUser($userId)
    {
        if (auth()->check()) {
            // 不屏蔽自己
            $authUser = auth()->id();
            if ($authUser != $userId) {
                $this->updateHiddenUsers($authUser, $userId);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function findByMany(array $ids)
    {
        $query = $this->model->query();
        return $query->whereIn("user_id", $ids)->get();
    }

    /**
     * @param $id
     * @return string
     * 屏蔽用户 redis key
     */
    public function hiddenUsersMemKey($id)
    {
        return 'user.' . $id . '.hidden.users';
    }

    /**
     * @param $id
     * @param $user_id
     * @return mixed
     * 添加屏蔽用户至redis
     */
    public function updateHiddenUsers($id, $user_id)
    {
        $userHiddenUsersKey = $this->hiddenUsersMemKey($id);
        if (!Redis::sismember($userHiddenUsersKey, $user_id)) {
            Redis::sadd($userHiddenUsersKey, $user_id);
            BlockUser::create(array(
                'user_id' => $id,
                'blocked_user_id' => $user_id,
            ));
        }
    }

    public function hiddenUsers($id = 0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        return Redis::sMembers($this->hiddenUsersMemKey($id));
    }

    /**
     * @deprecated
     * @version 1.0
     * @note 随机在线用户
     * @datetime 2021-07-12 19:46
     * @return mixed
     */
    protected function randRyOnlineUser()
    {
        $key = 'helloo:account:service:account-random-im-set';
        return Redis::srandmember($key);
    }

    /**
     * @deprecated
     * @version 1.0
     * @note 随机在线语音用户
     * @datetime 2021-07-12 19:46
     * @return mixed
     */
    protected function randRyVoiceUser()
    {
        $setKey = 'helloo:account:service:account-random-voice-set';
        return Redis::spop($setKey);
    }

    /**
     * @deprecated
     * @version
     * @note 随机在线视频用户
     * @datetime 2021-07-12 19:46
     * @return mixed
     */
    protected function randRyVideoUser()
    {
        $setKey = 'helloo:account:service:account-random-video-set';
        return Redis::spop($setKey);
    }

    /**
     * @deprecated
     * @note 随机在线Im用户
     * @datetime 2021-07-12 19:47
     * @param $self
     * @return int|mixed
     */
    public function randomIm($self)
    {
        $key = 'helloo:account:service:account-random-im-set';
        $maleKey = 'helloo:account:service:account-random-male-im-set';
        $femaleKey = 'helloo:account:service:account-random-female-im-set';
        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        $gender = Redis::zscore($genderSortSetKey , $self);
        if($gender!==null&&$gender!==false)
        {
            if($gender==0)
            {
                Redis::sadd($femaleKey , $self);
            }elseif($gender==1){
                Redis::sadd($maleKey , $self);
            }
        }
        $turn = 0;
        $s = 600000;
        while (true)
        {
            usleep($s);
            if($turn>5)
            {
                Redis::sadd($key , $self);
                $userId = 0;
                break;
            }
            $userId = $this->randRyOnlineUser();
            if(!empty($userId)&&$userId!=$self)
            {
                break;
            }
            $s = $s-50000;
            $turn++;
        }
        return $userId;
    }

    public function randomVideo($self)
    {
        $agent = new Agent();
        $deviceId = $agent->getHttpHeader('deviceId');
        $lockedKey = 'helloo:account:service:account-reported-locked:'.$self;
        Log::info('video match init:'.$self);
        if($this->deviceIdBlacklist($deviceId)||Redis::exists($lockedKey))
        {
            Log::info('video match user id:'.$self.' =>'  , array(
                'result'=>'be reported or blocked'
            ));
            $flag = false;
            $roomId = md5($self);
            return array('flag'=>$flag , 'roomId'=>$roomId);
        }

        if(intval(config('common.match_version'))!=0)
        {
            $data = $this->randomVideoV2($self);
        }else{
            $data = $this->randomVideoV1($self);
        }
        if($data['flag'])
        {
            if($this->official($data['userId']))
            {
                $locale = locale();
                if($locale=='id')
                {
                    $text = "Selamat ! Anda telah dipertemukan dengan Artis Lovbee";
                }elseif($locale=='zh-CN')
                {
                    $text = '恭喜您匹配到了Lovbee Star.';
                }else{
                    $text = 'Bingo！🎉 You have been matched with a Lovbee Star.';
                }
                $data['official'] = $text;
            }
        }
        return $data;

    }

    public function randomVideoV1($self)
    {
        $flag = false;
        $imKey = 'helloo:account:service:account-random-im-set';
        $setKey = 'helloo:account:service:account-random-video-set';
        $officialSetKey = 'helloo:account:service:account-random-official-video-set';
        $sortSetKey = 'helloo:account:service:account-random-video-sort-set';
        $cancelSetKey = 'helloo:account:service:account-cancel-video-random:'.$self;
        Redis::del($cancelSetKey);
        Redis::zadd($sortSetKey , time() , $self);
        Redis::sadd($imKey , $self);
        $isOfficial = $this->official($self);
        $turn = 1;
        $s = 600000;
        $userId = 0;
        while (true)
        {
            usleep($s);
            if($turn>10)
            {
                $userId = intval($this->randOfficialRyVideoUser());
                $userId = $this->isCancelVideoRandom($userId)?0:$userId;
                break;
            }
            if(!$this->isCancelVideoRandom($self))
            {
                $userId = intval($this->randRyVideoUser());
                $userId = $this->isCancelVideoRandom($userId)?0:$userId;
            }
            if(!empty($userId)&&$userId!=$self)
            {
                break;
            }

            $s = $s-50000;
            $turn++;
        }
        if($userId==0)
        {
            if($isOfficial)
            {
                $key = $officialSetKey;
            }else{
                $key = $setKey;
            }
            !$this->isCancelVideoRandom($self)&&Redis::sadd($key , $self);
            $roomId = md5($self);
            return array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            if($this->isCancelVideoRandom($self))
            {
                $roomId = md5($self);
                return array('flag'=>$flag , 'roomId'=>$roomId);
            }else{
                $flag = true;
                $roomId = md5($userId);
                return array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
            }
        }
    }

    public function randomVideoV2($self)
    {
        $user = auth()->user();
        $gender = intval(request()->input('gender' , 2));//0女 1男 2全部 -1没选择
        $gender = in_array($gender , array(0 , 1 , 2))?$gender:2;
        $userGender = intval($user->user_gender);
        $userNickName = $user->user_nick_name;
        Log::info('video match start user id:'.$self.' =>'  , array(
            'userId'=>$self,
            'userNickName'=>$userNickName,
            'gender'=>$gender,
            'userGender'=>$userGender,
        ));
        if($userGender===-1)
        {
            $roomId = md5($self);
            return array('flag'=>false , 'roomId'=>$roomId);
        }
        $imKey = 'helloo:account:service:account-random-im-set';
        $sortSetKey = 'helloo:account:service:account-random-video-sort-set';
        $cancelSetKey = 'helloo:account:service:account-cancel-video-random:'.$self;
        Redis::sadd($imKey , $self);
        Redis::zadd($sortSetKey , time() , $self);
        Redis::del($cancelSetKey);
        if($gender===0)
        {
            $data = $this->randomFemaleVideo($self , $userGender);
        }elseif ($gender===1)
        {
            $data = $this->randomMaleVideo($self , $userGender);
        }else{
            $data = $this->randomMixVideo($self , $userGender);
        }
        Log::info('video match result user id:'.$self.' user nick name:'.$userNickName.' =>' , $data);
        return $data;
    }

    public function randomVoice($self)
    {
        $flag = false;
        $lockedKey = 'helloo:account:service:account-reported-locked:'.$self;
        if(Redis::exists($lockedKey))
        {
            $roomId = md5($self);
            return array('flag'=>$flag , 'roomId'=>$roomId);
        }
        $imKey = 'helloo:account:service:account-random-im-set';
        $setKey = 'helloo:account:service:account-random-voice-set';
        $officialSetKey = 'helloo:account:service:account-random-official-voice-set';
        $sortSetKey = 'helloo:account:service:account-random-voice-sort-set';
        $cancelSetKey = 'helloo:account:service:account-cancel-voice-random:'.$self;
        Redis::del($cancelSetKey);
        Redis::zadd($sortSetKey , time() , $self);
        Redis::sadd($imKey , $self);
        $isOfficial = $this->official($self);
        $turn = 1;
        $s = 600000;
        $userId = 0;
        while (true)
        {
            usleep($s);
            if($turn>10)
            {
                $userId = intval($this->randOfficialRyVoiceUser());
                $userId = $this->isCancelVoiceRandom($userId)?0:$userId;
                break;
            }
            if(!$this->isCancelVoiceRandom($self))
            {
                $userId = intval($this->randRyVoiceUser());
                $userId = $this->isCancelVoiceRandom($userId)?0:$userId;
            }
            if(!empty($userId)&&$userId!=$self)
            {
                break;
            }
            $s = $s-50000;
            $turn++;
        }
        if($userId==0)
        {
            if($isOfficial)
            {
                $key = $officialSetKey;
            }else{
                $key = $setKey;
            }
            !$this->isCancelVoiceRandom($self)&&Redis::sadd($key , $self);
            $roomId = md5($self);
            return array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            if($this->isCancelVoiceRandom($self))
            {
                $roomId = md5($self);
                return array('flag'=>$flag , 'roomId'=>$roomId);
            }else{
                $flag = true;
                $roomId = md5($userId);
                return array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
            }
        }
    }

    public function randomVoiceV2($self)
    {
        $flag = false;
        $gender = intval(request()->input('gender' , -1));//0女 1男 2全部 -1没选择
        $sortSetKey = 'helloo:account:service:account-random-voice-sort-set';
        $imKey = 'helloo:account:service:account-random-im-set';
        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        $ageSortSetKey = 'helloo:account:service:account-age-sort-set';
        $setKey = 'helloo:account:service:account-random-voice-filter-set';
        $cancelSetKey = 'helloo:account:service:account-cancel-voice-random:'.$self;
        Redis::del($cancelSetKey);
        Redis::zadd($sortSetKey , time() , $self);
        Redis::sadd($imKey , $self);
        $redis = new RedisList();
        $isCancel = false;
        $lock = $redis->tryGetLock('helloo:account:service:processing_voice_matches_{helloo}', 1 , 10000);
        while (!$lock)
        {
            usleep(mt_rand(100000, 1000000));
            if($this->isCancelVoiceRandom($self))
            {
                $isCancel = true;
                break;
            }
            $lock = $redis->tryGetLock('helloo:account:service:processing_voice_matches_{helloo}', 1 , 10000);
        }
        if($isCancel)
        {
            $roomId = md5($self);
            return array('flag'=>$flag , 'roomId'=>$roomId);
        }
        $members = Redis::smembers($setKey);
        $members = array_diff($members , array($self));
        if(in_array($gender , array(0 , 1)))
        {
            !empty($members)&&$members = array_filter($members , function ($v , $k) use ($gender, $genderSortSetKey){
                $score = Redis::zscore($genderSortSetKey , $v);
                return $gender === $score;
            } , ARRAY_FILTER_USE_BOTH);
        }
        $age = intval(Redis::zscore($ageSortSetKey , $self));
        if(blank($members))
        {
            !$this->isCancelVoiceRandom($self)&&Redis::sadd($setKey , $self);
            $roomId = md5($self);
            $data = array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            if($age>0)
            {
                $ageData = array();
                foreach ($members as $member)
                {
                    $ageDiff = abs(intval(Redis::zscore($ageSortSetKey , $member))-$age);
                    $ageData[$member] = $ageDiff;
                }
                asort($ageData , SORT_NUMERIC);
                $ageData = array_slice(array_keys($ageData) , 0 , 3);
                $userId = $ageData[array_rand($ageData)];
            }else{
                $userId = $members[array_rand($members)];
            }
            if(Redis::sismember($setKey , $userId))
            {
                if(!$this->isCancelVoiceRandom($self))
                {
                    $flag = true;
                    Redis::srem($setKey , $userId);
                    Redis::srem($setKey , $self);
                    $roomId = md5($userId);
                    $data = array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
                }else{
                    $roomId = md5($self);
                    $data = array('flag'=>$flag , 'roomId'=>$roomId);
                }
            }else{
                !$this->isCancelVoiceRandom($self)&&Redis::sadd($setKey , $self);
                $roomId = md5($self);
                $data = array('flag'=>$flag , 'roomId'=>$roomId);
            }
        }
        $redis->releaseLock('helloo:account:service:processing_voice_matches_{helloo}');
        return $data;
    }

    public function removeVoice()
    {
        $self = auth()->id();
        $cancelSetKey = 'helloo:account:service:account-cancel-voice-random:'.$self;
        Redis::set($cancelSetKey, 1, "nx", "ex", 6);
        $setKey = 'helloo:account:service:account-random-voice-set';
        $filterSetKey = 'helloo:account:service:account-random-voice-filter-set';
        $set11Key = 'helloo:account:service:account-random-voice-filter-set-11';
        $set01Key = 'helloo:account:service:account-random-voice-filter-set-01';
        $set10Key = 'helloo:account:service:account-random-voice-filter-set-10';
        $set00Key = 'helloo:account:service:account-random-voice-filter-set-00';
        $officialSetKey = 'helloo:account:service:account-random-official-video-set';

        Redis::srem($setKey , $self);
        Redis::srem($filterSetKey , $self);
        Redis::srem($set11Key , $self);
        Redis::srem($set01Key , $self);
        Redis::srem($set10Key , $self);
        Redis::srem($set00Key , $self);
        Redis::srem($officialSetKey , $self);
    }

    public function removeVideo()
    {
        $self = auth()->id();
        $cancelSetKey = 'helloo:account:service:account-cancel-video-random:'.$self;
        Redis::set($cancelSetKey, 1, "nx", "ex", 6);
        $setKey = 'helloo:account:service:account-random-video-set';
        $filterSetKey = 'helloo:account:service:account-random-video-filter-set';

        $set00Key = 'helloo:account:service:account-random-video-filter-set-00';
        $set01Key = 'helloo:account:service:account-random-video-filter-set-01';
        $set02Key = 'helloo:account:service:account-random-video-filter-set-02';
        $set10Key = 'helloo:account:service:account-random-video-filter-set-10';
        $set11Key = 'helloo:account:service:account-random-video-filter-set-11';
        $set12Key = 'helloo:account:service:account-random-video-filter-set-12';

        Redis::srem($setKey , $self);
        Redis::srem($filterSetKey , $self);
        Redis::srem($set00Key , $self);
        Redis::srem($set01Key , $self);
        Redis::srem($set02Key , $self);
        Redis::srem($set10Key , $self);
        Redis::srem($set11Key , $self);
        Redis::srem($set12Key , $self);
    }

    public function isCancelVideoRandom($self)
    {
        $cancelSetKey = 'helloo:account:service:account-cancel-video-random:'.$self;
        return Redis::exists($cancelSetKey);
    }

    public function isCancelVoiceRandom($self)
    {
        $cancelSetKey = 'helloo:account:service:account-cancel-voice-random:'.$self;
        return Redis::exists($cancelSetKey);
    }

    public function updateUserOnlineState($users)
    {
//        RyOnline::dispatch($users)->onConnection('sqs-fifo')->onQueue('helloo_ry_user_online.fifo');
    }

    public function isOnline($id)
    {
        $bitKey = 'helloo:account:service:account-online-status-bit';
        $statue = Redis::getBit($bitKey, $id);
        return (bool)intval($statue);
    }

    /**
     * @note 点赞
     * @datetime 2021-07-12 19:42
     * @param $userId
     */
    public function like($userId)
    {
        $likedKey = 'helloo:account:service:account-liked-num';
        $authId = auth()->id();
        $like = DB::table('likes')->where('user_id' , $authId)->where('liked_id' , $userId)->first();
        if(empty($like))
        {
            $liked = DB::table('users')->where('user_id' , $userId)->first();
            if(empty($liked))
            {
                DB::table('likes')->insert(
                    array('user_id'=>$authId , 'liked_id'=>$userId)
                );
                $likeNum = DB::table('likes')->where('liked_id' , $userId)->count();
                Redis::zadd($likedKey , $likeNum , $userId);
            }
        }
    }

    /**
     * @deprecated
     * @note 用户中心页点赞
     * @datetime 2021-07-12 19:41
     * @param $id
     */
    public function profileLike($id)
    {
        $likeUser = auth()->user();
        $likeUserId = $likeUser->user_id;
        $userProfileLikeKey = 'user.' . $id . '.profile.like';
        if (Redis::zrank($userProfileLikeKey, $likeUserId) === null) {
            $user = $this->findOrFail($id);
            $like = $likeUser->profileLike()->updateOrCreate(array('profile_user_id' => $user->getKey()));
            event(new UserProfileLikeEvent($likeUser, $user, $like));
        }
    }

    /**
     * @deprecated
     * @note 用户中心页取消点赞
     * @datetime 2021-07-12 19:41
     * @param $id
     */
    public function profileRevokeLike($id)
    {
        $likeUser = auth()->user();
        $likeUserId = $likeUser->user_id;
        $userProfileLikeKey = 'user.' . $id . '.profile.like';
        if (Redis::zrank($userProfileLikeKey, $likeUserId) !== null) {
            $user = $this->findOrFail($id);
            $likeUser->profileLike()->delete();
            event(new UserProfileRevokeLikeEvent($likeUser, $user));
        }
    }


    /**
     * @deprecated
     * @note 在线用户数量
     * @datetime 2021-07-12 19:40
     * @return mixed
     */
    public function onlineUsersCount()
    {
        $key = 'helloo:account:service:account-random-im-set';
        return Redis::scard($key);
    }

    /**
     * @deprecated
     * @note 星球
     * @datetime 2021-07-12 19:40
     * @return array
     */
    public function planet()
    {
        $num = intval(request()->input('num', 55));
        $num = $num < 10 || $num > 100 ? 50 : $num;
        $key = 'helloo:account:service:account-random-im-set';
        $userIds = Redis::srandmember($key, $num);
        $backup = array(
            1,
            2,
            63,
            65,
            66,
            67,
            68,
            69,
            70,
            71,
            73,
            158,
            159,
            160,
            161,
            162,
            163,
            164,
            165,
            166,
            232,
            233,
            260,
            261,
            268,
            272,
            273,
            274,
            275,
            277,
            279,
            288,
            292,
            300,
            302,
            303,
            304,
            336,
            337,
            338,
            339,
            340,
            341,
            342,
            343,
            344,
            345,
            346,
            347,
            349,
            350,
            351,
            352,
            353,
            357,
        );
        if(count($userIds)<=50)
        {
            $userIds = array_merge($userIds , $backup);
            $userIds = array_unique($userIds);
            shuffle($userIds);
        }
        return array_slice($userIds , 0 , 52);
    }

    /**
     * @note 随机获取官方视频(陪聊)
     * @datetime 2021-07-12 19:38
     * @return mixed
     */
    protected function randOfficialRyVideoUser()
    {
        $setKey = 'helloo:account:service:account-random-official-video-set';
        return Redis::spop($setKey);
    }

    /**
     * @deprecated
     * @note 随机获取官方语音(陪聊)
     * @datetime 2021-07-12 19:38
     * @return mixed
     */
    protected function randOfficialRyVoiceUser()
    {
        $setKey = 'helloo:account:service:account-random-official-voice-set';
        return Redis::spop($setKey);
    }

    /**
     * @deprecated
     * @note 设备屏蔽列表
     * @datetime 2021-07-12 19:37
     * @param $deviceId
     * @return bool
     */
    public function deviceIdBlacklist($deviceId)
    {
        $key = 'helloo:account:service:device-id-blacklist';
        return boolval(Redis::zrank($key , $deviceId)!==null);
    }

    /**
     * @deprecated
     * @note 官方(陪聊)状态
     * @datetime 2021-07-12 19:37
     * @param $userId
     * @return bool
     */
    public function official($userId)
    {
        $key = 'helloo:account:service:account-official';
        return boolval(Redis::zrank($key , $userId)!==null);
    }

    /**
     * @deprecated
     * @note 官方(陪聊)在线
     * @datetime 2021-07-12 19:36
     * @param $userId
     */
    public function officialSet($userId)
    {
        $setKey = 'helloo:account:service:account-random-official-video-set';
        Redis::sadd($setKey , $userId);
    }

    /**
     * @deprecated
     * @note 视频匹配(男)
     * @datetime 2021-07-12 19:36
     * @param $self
     * @param $userGender
     * @return array
     */
    public function randomMaleVideo($self , $userGender)
    {
        $flag = false;
        $ageSortSetKey = 'helloo:account:service:account-age-sort-set';
        $set00Key = 'helloo:account:service:account-random-video-filter-set-00';
        $set01Key = 'helloo:account:service:account-random-video-filter-set-01';
        $set02Key = 'helloo:account:service:account-random-video-filter-set-02';
        $set10Key = 'helloo:account:service:account-random-video-filter-set-10';
        $set11Key = 'helloo:account:service:account-random-video-filter-set-11';
        $set12Key = 'helloo:account:service:account-random-video-filter-set-12';
        if($userGender==1)
        {
            $matchKey = array(
                $set11Key,
                $set12Key,
            );
            $setKey = $set11Key;
        }elseif ($userGender==0)
        {
            $matchKey = array(
                $set10Key,
                $set12Key,
            );
            $setKey = $set01Key;
        }else{
            $setKey = 'helloo:account:service:account-random-video-filter-set';
            $matchKey = array(
                $setKey
            );
        }
        $members = array();
        Log::info('video match user id:'.$self.' =>'  , array(
            'matchKey'=>$matchKey
        ));
        array_walk($matchKey , function($v , $k) use (&$members){
            $members = array_merge($members , Redis::smembers($v));
        });
        Log::info('video match user id:'.$self.' =>'  , array(
            'members'=>$members
        ));
        if(blank($members))
        {
            Log::info('video match user id:'.$self.' =>'  , array(
                'result'=>'members no data one'
            ));
            if(!$this->isCancelVideoRandom($self))
            {
                Redis::sadd($setKey , $self);
            }
            $roomId = md5($self);
            $data = array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            $i = 1;
            $status = false;
            $members = array_diff($members , array($self));
            while (count($members)<5&&$i<=5)
            {
                if($this->isCancelVideoRandom($self))
                {
                    $status = true;
                    break;
                }
                array_walk($matchKey , function($v , $k) use (&$members){
                    $members = array_merge($members , Redis::smembers($v));
                });
                $members = array_diff($members , array($self));
                usleep(mt_rand(500000, 1000000));
                $i++;
            }
            if($status)
            {
                Log::info('video match user id:'.$self.' =>'  , array(
                    'result'=>'member cancel video one'
                ));
                $roomId = md5($self);
                return array('flag'=>$flag , 'roomId'=>$roomId);
            }

            if(blank($members))
            {
                Log::info('video match user id:'.$self.' =>'  , array(
                    'result'=>'members no data two'
                ));
                !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                $roomId = md5($self);
                $data = array('flag'=>$flag , 'roomId'=>$roomId);
            }else{
                $age = intval(Redis::zscore($ageSortSetKey , $self));
                if($age>0)
                {
                    $ageData = array();
                    foreach ($members as $member)
                    {
                        $ageDiff = abs(intval(Redis::zscore($ageSortSetKey , $member))-$age);
                        $ageData[$member] = $ageDiff;
                    }
                    asort($ageData , SORT_NUMERIC);
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'ageData'=>$ageData
                    ));
                    $ageData = array_slice(array_keys($ageData) , 0 , 2);
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'ageDataSlice'=>$ageData
                    ));
                    $userId = $ageData[array_rand($ageData)];
                }else{
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'age'=>$age
                    ));
                    $userId = $members[array_rand($members)];
                }
                if($this->isCancelVideoRandom($userId))
                {
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'userId'=>$userId,
                        'result'=>'member cancel video two'
                    ));
                    $userId = 0;
                }else{
                    array_walk($matchKey , function($v , $k) use (&$members){
                        $members = array_merge($members , Redis::smembers($v));
                    });
                    if(!in_array($userId , $members))
                    {
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'userId'=>$userId,
                            'result'=>'member cancel video three'
                        ));
                        $userId = 0;
                    }
                }
                if($userId==0)
                {
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'result'=>'failure'
                    ));
                    !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                    $roomId = md5($self);
                    $data = array('flag'=>$flag , 'roomId'=>$roomId);
                }else{
                    array_walk($matchKey , function($v , $k) use ($userId){
                        Redis::srem($v , $userId);
                    });
                    $redis = new RedisList();
                    $lock = $redis->tryGetLock('helloo:account:service:processing_video_matches_{helloo}_'.$userId, 1 , 5000);
                    if($lock)
                    {
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'result'=>'success',
                            'userId'=>$userId,
                        ));
                        $flag = true;
                        $roomId = md5($userId);
                        $data = array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
                    }else{
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'result'=>'failure',
                            'userId'=>$userId,
                            'reason'=>'already matched',
                        ));
                        !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                        $roomId = md5($self);
                        $data = array('flag'=>$flag , 'roomId'=>$roomId);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @deprecated
     * @note 视频匹配(女)
     * @datetime 2021-07-12 19:35
     * @param $self
     * @param $userGender
     * @return array
     */
    public function randomFemaleVideo($self , $userGender)
    {
        $flag = false;
        $ageSortSetKey = 'helloo:account:service:account-age-sort-set';
        $set00Key = 'helloo:account:service:account-random-video-filter-set-00';
        $set01Key = 'helloo:account:service:account-random-video-filter-set-01';
        $set02Key = 'helloo:account:service:account-random-video-filter-set-02';
        $set10Key = 'helloo:account:service:account-random-video-filter-set-10';
        $set11Key = 'helloo:account:service:account-random-video-filter-set-11';
        $set12Key = 'helloo:account:service:account-random-video-filter-set-12';
        if($userGender==1)
        {
            $matchKey = array(
                $set01Key,
                $set02Key,
            );
            $setKey = $set10Key;
        }elseif ($userGender==0)
        {
            $matchKey = array(
                $set00Key,
                $set02Key,
            );
            $setKey = $set00Key;
        }else{
            $setKey = 'helloo:account:service:account-random-video-filter-set';
            $matchKey = array(
                $setKey
            );
        }
        $members = array();
        Log::info('video match user id:'.$self.' =>'  , array(
            'matchKey'=>$matchKey
        ));
        array_walk($matchKey , function($v , $k) use (&$members){
            $members = array_merge($members , Redis::smembers($v));
        });
        Log::info('video match user id:'.$self.' =>'  , array(
            'members'=>$members
        ));
        if(blank($members))
        {
            Log::info('video match user id:'.$self.' =>'  , array(
                'result'=>'members no data one'
            ));
            if(!$this->isCancelVideoRandom($self))
            {
                Redis::sadd($setKey , $self);
            }
            $roomId = md5($self);
            $data = array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            $i = 1;
            $status = false;
            $members = array_diff($members , array($self));
            while (count($members)<5&&$i<=5)
            {
                if($this->isCancelVideoRandom($self))
                {
                    $status = true;
                    break;
                }
                array_walk($matchKey , function($v , $k) use (&$members){
                    $members = array_merge($members , Redis::smembers($v));
                });
                $members = array_diff($members , array($self));
                usleep(mt_rand(500000, 1000000));
                $i++;
            }
            if($status)
            {
                Log::info('video match user id:'.$self.' =>'  , array(
                    'result'=>'member cancel video one'
                ));
                $roomId = md5($self);
                return array('flag'=>$flag , 'roomId'=>$roomId);
            }

            if(blank($members))
            {
                Log::info('video match user id:'.$self.' =>'  , array(
                    'result'=>'members no data two'
                ));
                !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                $roomId = md5($self);
                $data = array('flag'=>$flag , 'roomId'=>$roomId);
            }else{
                $age = intval(Redis::zscore($ageSortSetKey , $self));
                if($age>0)
                {
                    $ageData = array();
                    foreach ($members as $member)
                    {
                        $ageDiff = abs(intval(Redis::zscore($ageSortSetKey , $member))-$age);
                        $ageData[$member] = $ageDiff;
                    }
                    asort($ageData , SORT_NUMERIC);
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'ageData'=>$ageData
                    ));
                    $ageData = array_slice(array_keys($ageData) , 0 , 1);
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'ageDataSlice'=>$ageData
                    ));
                    $userId = $ageData[array_rand($ageData)];
                }else{
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'age'=>$age
                    ));
                    $userId = $members[array_rand($members)];
                }
                if($this->isCancelVideoRandom($userId))
                {
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'userId'=>$userId,
                        'result'=>'member cancel video two'
                    ));
                    $userId = 0;
                }else{
                    array_walk($matchKey , function($v , $k) use (&$members){
                        $members = array_merge($members , Redis::smembers($v));
                    });
                    if(!in_array($userId , $members))
                    {
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'userId'=>$userId,
                            'result'=>'member cancel video three'
                        ));
                        $userId = 0;
                    }
                }
                if($userId==0)
                {
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'result'=>'failure'
                    ));
                    !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                    $roomId = md5($self);
                    $data = array('flag'=>$flag , 'roomId'=>$roomId);
                }else{
                    array_walk($matchKey , function($v , $k) use ($userId){
                        Redis::srem($v , $userId);
                    });
                    $redis = new RedisList();
                    $lock = $redis->tryGetLock('helloo:account:service:processing_video_matches_{helloo}_'.$userId, 1 , 5000);
                    if($lock)
                    {
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'result'=>'success',
                            'userId'=>$userId,
                        ));
                        $flag = true;
                        $roomId = md5($userId);
                        $data = array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
                    }else{
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'result'=>'failure',
                            'userId'=>$userId,
                            'reason'=>'already matched',
                        ));
//                        $redis->releaseLock('helloo:account:service:processing_video_matches_{helloo}_'.$userId);
                        !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                        $roomId = md5($self);
                        $data = array('flag'=>$flag , 'roomId'=>$roomId);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @deprecated
     * @note 视频匹配
     * @datetime 2021-07-12 19:32
     * @param $self
     * @param $userGender
     * @return array
     */
    public function randomMixVideo($self , $userGender)
    {
        $flag = false;
        $ageSortSetKey = 'helloo:account:service:account-age-sort-set';
        $set00Key = 'helloo:account:service:account-random-video-filter-set-00';
        $set01Key = 'helloo:account:service:account-random-video-filter-set-01';
        $set02Key = 'helloo:account:service:account-random-video-filter-set-02';
        $set10Key = 'helloo:account:service:account-random-video-filter-set-10';
        $set11Key = 'helloo:account:service:account-random-video-filter-set-11';
        $set12Key = 'helloo:account:service:account-random-video-filter-set-12';
        if($userGender==1)
        {
            $matchKey = array(
                $set01Key,
                $set02Key,
                $set11Key,
                $set12Key,
            );
            $setKey = $set12Key;
        }elseif ($userGender==0)
        {
            $matchKey = array(
                $set00Key,
                $set02Key,
                $set10Key,
                $set12Key,
            );
            $setKey = $set02Key;
        }else{
            $setKey = 'helloo:account:service:account-random-video-filter-set';
            $matchKey = array(
                $setKey
            );
        }
        $members = array();
        Log::info('video match user id:'.$self.' =>'  , array(
            'matchKey'=>$matchKey
        ));
        array_walk($matchKey , function($v , $k) use (&$members){
            $members = array_merge($members , Redis::smembers($v));
        });
        Log::info('video match user id:'.$self.' =>'  , array(
            'members'=>$members
        ));
        if(blank($members))
        {
            Log::info('video match user id:'.$self.' =>'  , array(
                'result'=>'members no data one'
            ));
            if(!$this->isCancelVideoRandom($self))
            {
                Redis::sadd($setKey , $self);
            }
            $roomId = md5($self);
            $data = array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            $i = 1;
            $status = false;
            $members = array_diff($members , array($self));
            while (count($members)<5&&$i<=5)
            {
                if($this->isCancelVideoRandom($self))
                {
                    $status = true;
                    break;
                }
                array_walk($matchKey , function($v , $k) use (&$members){
                    $members = array_merge($members , Redis::smembers($v));
                });
                $members = array_diff($members , array($self));
                usleep(mt_rand(500000, 1000000));
                $i++;
            }
            if($status)
            {
                Log::info('video match user id:'.$self.' =>'  , array(
                    'result'=>'member cancel video one'
                ));
                $roomId = md5($self);
                return array('flag'=>$flag , 'roomId'=>$roomId);
            }

            if(blank($members))
            {
                Log::info('video match user id:'.$self.' =>'  , array(
                    'result'=>'members no data two'
                ));
                !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                $roomId = md5($self);
                $data = array('flag'=>$flag , 'roomId'=>$roomId);
            }else{
                $age = intval(Redis::zscore($ageSortSetKey , $self));
                if($age>0)
                {
                    $ageData = array();
                    foreach ($members as $member)
                    {
                        $ageDiff = abs(intval(Redis::zscore($ageSortSetKey , $member))-$age);
                        $ageData[$member] = $ageDiff;
                    }
                    asort($ageData , SORT_NUMERIC);
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'ageData'=>$ageData
                    ));
                    $ageData = array_slice(array_keys($ageData) , 0 , 2);
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'ageDataSlice'=>$ageData
                    ));
                    $userId = $ageData[array_rand($ageData)];
                }else{
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'age'=>$age
                    ));
                    $userId = $members[array_rand($members)];
                }
                if($this->isCancelVideoRandom($userId))
                {
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'userId'=>$userId,
                        'result'=>'member cancel video two'
                    ));
                    $userId = 0;
                }else{
                    array_walk($matchKey , function($v , $k) use (&$members){
                        $members = array_merge($members , Redis::smembers($v));
                    });
                    if(!in_array($userId , $members))
                    {
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'userId'=>$userId,
                            'result'=>'member cancel video three'
                        ));
                        $userId = 0;
                    }
                }
                if($userId==0)
                {
                    Log::info('video match user id:'.$self.' =>'  , array(
                        'result'=>'failure'
                    ));
                    !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                    $roomId = md5($self);
                    $data = array('flag'=>$flag , 'roomId'=>$roomId);
                }else{
                    array_walk($matchKey , function($v , $k) use ($userId){
                        Redis::srem($v , $userId);
                    });
                    $redis = new RedisList();
                    $lock = $redis->tryGetLock('helloo:account:service:processing_video_matches_{helloo}_'.$userId, 1 , 5000);
                    if($lock)
                    {
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'result'=>'success',
                            'userId'=>$userId,
                        ));
                        $flag = true;
                        $roomId = md5($userId);
                        $data = array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
                    }else{
                        Log::info('video match user id:'.$self.' =>'  , array(
                            'result'=>'failure',
                            'userId'=>$userId,
                            'reason'=>'already matched',
                        ));
//                        $redis->releaseLock('helloo:account:service:processing_video_matches_{helloo}_'.$userId);
                        !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
                        $roomId = md5($self);
                        $data = array('flag'=>$flag , 'roomId'=>$roomId);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @note 用户名弹窗
     * @datetime 2021-07-12 19:32
     * @param $userId
     * @return int
     */
    public function usernamePrompt($userId)
    {

        $key = "helloo:account:service:username:prompt:account:".$userId;
        if(Redis::exists($key))
        {
            $result = intval(Redis::get($key));
        }else{
            $prompt = DB::table('username_prompt')->where('user_id' , $userId)->first();
            if(!blank($prompt))
            {
                $result = 1;
                Redis::set($key, $result, "nx", "ex", 60*60*24*15);
            }else{
                $result = 0;
                Redis::set($key, $result, "nx", "ex", 60*60*24*15);
            }
        }
        return $result;
    }

    public function updateUsernamePrompt($userId)
    {
        $prompt = DB::table('username_prompt')->where('user_id' , $userId)->first();
        if(blank($prompt))
        {
            $result = DB::table('username_prompt')->insert(array(
                'id'=>app('snowflake')->id(),
                'user_id'=>$userId,
                'created_at'=>Carbon::now()->timestamp,
            ));
            if($result)
            {
                $key = "helloo:account:service:username:prompt:account:".$userId;
                Redis::del($key);
                Redis::set($key, 1, "nx", "ex", 60*60*24*15);
            }
        }
    }

    /**
     * @note 获取商家评分
     * @datetime 2021-07-12 19:44
     * @param $userId
     * @return array
     */
    public function findPointByUserId($userId)
    {
        $key = "helloo:account:point:service:account:".$userId;
        $point = Redis::get($key);
        if(empty($point))
        {
            $shopPoint = collect(DB::table('shop_evaluation_points')->where('user_id' , $userId)->first());
            if(!blank($shopPoint))
            {
                $point_1 = $shopPoint->get('point_1' , 0);
                $point_2 = $shopPoint->get('point_2' , 0);
                $point_3 = $shopPoint->get('point_3' , 0);
                $point_4 = $shopPoint->get('point_4' , 0);
                $point_5 = $shopPoint->get('point_5' , 0);
                $pointNum = $point_1+$point_2+$point_3+$point_4+$point_5;
                $pointSum = $point_1+2*$point_2+3*$point_3+4*$point_4+5*$point_5;
                $data = collect(array(
                    'item'=>array(
                        'point_1'=>$point_1,
                        'point_2'=>$point_2,
                        'point_3'=>$point_3,
                        'point_4'=>$point_4,
                        'point_5'=>$point_5,
                    ),
                    'sum'=>$pointSum,
                    'num'=>$pointNum
                ));
            }else{
                $data = collect(array(
                    'item'=>array(
                        'point_1'=>0,
                        'point_2'=>0,
                        'point_3'=>0,
                        'point_4'=>0,
                        'point_5'=>0,
                    ),
                    'sum'=>0,
                    'num'=>0
                ));
            }
            $cache = $data->toArray();
            Redis::set($key , \json_encode($cache));
            Redis::expire($key , 60*60*24);
        }else{
            $data = collect(\json_decode($point , true));
        }
        $percentage = array();
        $item = $data->get('item' , array());
        $sum = $data->get('sum' , 0);
        $num = $data->get('num' , 0);
        foreach ($item as $k=>$i)
        {
            $numerator = $num>0?$i/$num*100:0;
            $percentage[$k] = round($numerator , 1);
        }
        return array(
            'percentage'=>$percentage,
            'point'=>$num>0?round($sum/$num , 1):0.0,
            'comment'=>formatNumber($num),
        );
    }

}
