<?php

namespace App\Http\Controllers\V1;

use App\Jobs\MoreTimeUserScoreUpdate;
use App\Models\Country;
use App\Models\LikePhoto;
use App\Models\LikeVideo;
use App\Models\Photo;
use App\Models\UserKpiCount;
use App\Models\User;
use App\Models\UserFriend;
use App\Models\UserFriendRequest;
use App\Models\Video;
use App\Repositories\Contracts\UserFriendRepository;
use App\Traits\CacheableScore;
use Illuminate\Validation\Rule;
use App\Repositories\Contracts\UserRepository;
use App\Resources\UserCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class UserCenterController extends BaseController
{
    use CacheableScore;
    private $user;
    private $userId;

    public function __construct()
    {
        $this->user   = auth()->user();
        $this->userId = auth()->id();
    }

    /**
     * @return int
     * 总积分
     */
    public function totalScore()
    {
        $memKey = 'helloo:account:user-score-rank';
        $score  = Redis::zscore($memKey, $this->userId);
        return $this->response->array(['score'=>$score]);
    }

    /**
     * @param string $friendId
     * @return array
     * 获取照片、视频列表
     */
    public function media($friendId='')
    {
        $video = $photo = $friend = false;

        if (!empty($friendId) && $friendId!=$this->userId) {
            //个人隐私设置
            $mKey    = 'helloo:account:service:account-privacy:'.$friendId;
            $privacy = Redis::get($mKey);
            $setting = !empty($privacy) ? json_decode($privacy, true) : ['friend'=>"1", 'video'=>"1",'photo'=>"1"];
            $friends = UserFriend::where('user_id' , $this->userId)->where('friend_id', $friendId)->first();

            if ($setting['friend']=='1' || ($setting['friend']=='2' && !empty($friends))) {
                $friend = true;
            }
            if ($setting['video']=='1' || ($setting['video']=='2' && !empty($friends))) {
                $video = true;
            }
            if ($setting['photo']=='1' || ($setting['photo']=='2' && !empty($friends))) {
                $photo = true;
            }
        } else {
            $video    = $photo = $friend = true;
            $friendId = $this->userId;
        }

        $friend && $result['friend'] = $this->getFriends($friendId);
        $video  && $result['video']  = $this->getVideos($friendId);
        $photo  && $result['photo']  = $this->getPhotos($friendId);

        return $result ?? [];
    }

    /**
     * @param $userId
     * @return mixed
     * 获取前十个好友
     */
    public function getFriends($userId)
    {
        $userFriends = app(UserFriendRepository::class)->getAllByUser($userId, 10);
        $friendIds   = $userFriends->pluck('friend_id')->all();
        if (empty($friendIds)) {
            return null;
        }
        $friends     = app(UserRepository::class)->findByMany($friendIds);
        $userFriends->each(function($userFriend) use ($friends){
            $userFriend->friend = new UserCollection($friends->where('user_id', $userFriend->friend_id)->first());
        });

        return $userFriends;
    }

    /**
     * @param $userId
     * @return mixed
     * 获取Vlog Videos
     */
    public function getVideos($userId)
    {
        $videos = Video::select('video_id', 'image', 'like', 'video_url')->where('user_id', $userId)->orderByDesc('created_at')->limit(10)->get();
        $videoIds = $videos->pluck('video_id')->toArray();
        if (empty($videoIds)) {
            return [];
        }
        // 查询点赞表
        $likes = LikeVideo::where('user_id', $this->userId)->whereIn('liked_id', $videoIds)->get();
        foreach ($videos as $video) {
            $video->isLiked = false;
            foreach ($likes as $like) {
                if ($like->liked_id==$video->video_id) {
                    $video->isLiked = true;
                }
            }
        }
        return $videos;
    }

    /**
     * @param $userId
     * @return mixed
     * 获取照片墙
     */
    public function getPhotos($userId)
    {
        $photos = Photo::select('photo_id', 'photo', 'like')->where('user_id', $userId)->orderByDesc('created_at')->limit(10)->get();
        $photoIds = $photos->pluck('photo_id')->toArray();
        if (empty($photoIds)) {
            return [];
        }
        // 查询点赞表
        $likes = LikePhoto::where('user_id', $this->userId)->whereIn('liked_id', $photoIds)->get();
        foreach ($photos as $photo) {
            $photo->isLiked = false;
            foreach ($likes as $like) {
                if ($like->liked_id==$photo->photo_id) {
                    $photo->isLiked = true;
                }
            }
        }
        return $photos;
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * 个人中心 保存图片、视频
     */
    public function storeMedia(Request $request)
    {
        $params = $request->all();
        $valid  = [
            'type'=> [
                'required',
                Rule::in(['video', 'photo'])
            ],
            'image' => 'required|array|max:10',
            'video_url' => 'sometimes|array|max:10'
        ];
        $this->validate($request, $valid);

        $images    = $request->input('image');
        $video_url = $request->input('video_url');

        $model = $params['type'] == 'video' ? new Video() : new Photo();
        $image = $params['type'] == 'video' ? 'image' : 'photo';
        $count = $model->where('user_id', $this->userId)->count();
        if ($count>=10) {
            return $this->response->errorNotFound('Extra limit, maximum 10');
        }
        Log::info('上传参数：', $request->all());
        if (count($images)+$count>10) {
            return $this->response->errorNotFound('Extra limit, maximum 10');
        }

        foreach ($images as $key=>$item) {
            $data = [];
            $data[$model->getKeyName()] = app('snowflake')->id();
            $data['user_id'] = $this->userId;
            $data[$image] = $item;

            if ($params['type'] =='video') {
                $data['video_url']   = $video_url[$key];
                $data['bundle_name'] = $params['mask'] ?? '';
            }
            $model->create($data);
        }

        return $this->response->accepted();
    }

    /**
     * @param $type
     * @param $id
     * @return \Dingo\Api\Http\Response|void
     * 个人中心 删除图片、视频
     */
    public function destroyMedia($id, $type)
    {
        $table  = $type == 'video' ? 'users_videos' : 'users_photos';
        $kId    = $type == 'video' ? 'video_id'     : 'photo_id';
        $result = DB::table($table)->where('user_id', $this->userId)->where($kId, $id)->first();
        if (empty($result)) {
            return $this->response->accepted();
        }

        DB::beginTransaction();
        try {
            $delete = DB::table($table)->where('user_id', $this->userId)->where($kId, $id)->delete();
            if ($delete) {
                $result->deleted_at = date('Y-m-d H:i:s');
                DB::table($table."_logs")->insert(collect($result)->toArray());

                // 减积分
                $params['user_id']    = $this->userId;
                $params['type']       = 'delMedia';
                $params['sourceType'] = $type;
                $params['id']         = $id;
                $this->delMedia($params);
                DB::commit();
                return $this->response->accepted();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('delete fail:', ['code'=>$e->getCode(), 'message'=>$e->getMessage()]);
        }
        return $this->response->error('删除失败，请稍后重试',400);

    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 修改隐私配置
     */
    public function updatePrivacy(Request $request)
    {
        $s = [1,2,3];
        $rules = [
            'friend' => [
                'required',
                Rule::in($s)
            ],
            'video' => [
                'required',
                Rule::in($s)
            ],
            'photo' => [
                'required',
                Rule::in($s)
            ]
        ];

        $params = $request->only('friend', 'video', 'photo');
        Validator::make($params, $rules)->validate();

        $params['updated_at'] = date('Y-m-d H:i:s');

        // 临时用
        $select = DB::table('users_settings')->where('user_id', $this->userId)->first();
        if (empty($select)) {
            $in = [
                'user_id' => $this->userId,
                'friend'  => 1,
                'video'   => 1,
                'photo'   => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
           DB::table('users_settings')->insert($in);
        }

        $result = DB::table('users_settings')->where('user_id', $this->userId)->update($params);
        if (!empty($result)) {
            $mKey = 'helloo:account:service:account-privacy:'.$this->userId;
            Redis::set($mKey, json_encode($params));
            Redis::expire($mKey , 86400*7);
            return $this->response->accepted();
        } else {
            return $this->response->errorNotFound();
        }
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 点赞Video/Photo
     */
    public function like(Request $request)
    {
        $rules = [
            'type' => ['required', Rule::in(array('video' , 'photo'))],
            'id'   => ['required'],
        ];
        $type  = $request->input('type', '');
        $id    = $request->input('id', 0);
        $params = array('type'=>$type , 'id'=>$id);
        Validator::make($params, $rules)->validate();
        $time = date('Y-m-d H:i:s');
        $model = $type == 'video' ? new Video() : new Photo();
        $model = $model->findOrFail($id);

        Log::info('点赞：like:'.$this->userId. ' liked:'.$model->user_id);
        $like  = $type == 'video' ? new LikeVideo() : new LikePhoto();
        $check = $like->where(['user_id'=>$this->userId, 'liked_id'=>$id])->first();
        if (empty($check)) {
            $snowId =  app('snowflake')->id();
            $data['id'] = $snowId;
            $data['user_id'] = $this->userId;
            $data['liked_id'] = $id;
            $data['created_at'] = $time;
            $like->insert($data);
            if($type=='video')
            {
                $likeCount = DB::table('users_kpi_counts')->where('user_id' , $this->userId)->first();
                if(blank($likeCount))
                {
                    DB::table('users_kpi_counts')->insert(array(
                        'user_id'=>$this->userId,
                        'like'=>1,
                        'like_video'=>1,
                        'created_at'=>$time,
                        'updated_at'=>$time,
                    ));
                }else{
                    DB::table('users_kpi_counts')->where('user_id' , $model->user_id)->update(array(
                        'like'=>DB::raw('like+1'),
                        'like_video'=>DB::raw('like_video+1'),
                        'updated_at'=>$time,
                    ));
                }
                MoreTimeUserScoreUpdate::dispatch($this->userId , 'likeVideo' , $snowId)->onQueue('helloo_{more_time_user_score_update}');
                $likedCount = DB::table('users_kpi_counts')->where('user_id' , $model->user_id)->first();
                if(blank($likedCount))
                {
                    DB::table('users_kpi_counts')->insert(array(
                        'user_id'=>$model->user_id,
                        'liked'=>1,
                        'liked_video'=>1,
                        'created_at'=>$time,
                        'updated_at'=>$time,
                    ));
                }else{
                    DB::table('users_kpi_counts')->where('user_id' , $model->user_id)->update(array(
                        'liked'=>DB::raw('liked+1'),
                        'liked_video'=>DB::raw('liked_video+1'),
                        'updated_at'=>$time,
                    ));
                }
                MoreTimeUserScoreUpdate::dispatch($model->user_id , 'likedVideo' , $snowId)->onQueue('helloo_{more_time_user_score_update}');
            }

        }
        $model->like +=1;
        $model->save();
       return $this->response->accepted();
    }

    /**
     * 获取勋章列表
     * @param $userId
     * @return array
     */
    public function medal($userId)
    {
        if (empty((int)$userId)) {
            return $this->response->errorNotFound('参数异常 userId 不能为空');
        }
        $userInfo = User::findOrFail($userId);
        $locale = locale();

        $locale = $locale == 'zh-CN' ? 'cn' : ($locale =='id' ? $locale : 'en');
        $result = DB::table('medals')->select('title', 'name', 'desc', 'image_light', 'image', 'score', 'category')->get();
        $medals = [];
        $day    = date('Y-m-d');

        $vlog   = DB::table('users_videos')->where('user_id', $userId)->get()->count();
        $photo  = DB::table('users_photos')->where('user_id', $userId)->count();
        $statistic = UserKpiCount::where('user_id', $this->userId)->first();
        $statistic = !empty($statistic) ? $statistic : new UserKpiCount();

        $mKey       = "helloo:message:service:mutual-video-geq-ten".$day;
        $memKey     = "helloo:message:service:mutual-txt-geq-ten".$day;

        $tenText    = Redis::sismember($memKey, $userId);
        $tenVideo   = Redis::sismember($mKey, $userId);
        $categories = $result->pluck('category')->unique()->toArray();
        $num = 0;
        foreach ($result as $item) {
            foreach ($categories as $category) {
                if ($category==$item->category) {
                    $name = json_decode($item->name, true);
                    $desc = json_decode($item->desc, true);
                    $item->name = $name[$locale];
                    $item->desc = $desc[$locale];
                    $flag = $this->status($item, $userInfo, $statistic, $vlog, $photo, $tenVideo, $tenText);
                    $item->flag  = empty($flag) ? -1 : ($flag===true ? -2 : $flag);
                    $item->image = empty($flag) ? $item->image : $item->image_light;
                    $flag && $num++;
                    $medals[$category][] = collect($item)->except(['title', 'category', 'image_light']);
                }
            }
       }
        $medals['achievements'] = $num."/".count($result);

        // 积分 排行
        $memKey = 'helloo:account:user-score-rank';
        $rank   = Redis::zrevrank($memKey , $userId);
        $rank   = !empty($rank) ? $rank : Redis::zcard($memKey);
        $medals['rank']   = (int)$rank+1;
        $medals['score']  = (int)Redis::zscore($memKey, $userId);
        $medals['avatar'] = userCover($userInfo->user_avatar);
        return $medals;
    }

    /**
     * @param $media
     * @param $userInfo
     * @param $statistic
     * @param $vlog
     * @param $photo
     * @param $tenVideo
     * @param $tenText
     * @return bool
     * 奖章状态
     */
    public function status($media, $userInfo, $statistic, $vlog, $photo, $tenVideo, $tenText)
    {
        switch (trim($media->title)) {
            case 'Profile picture': // 个人头像
                $flag = stristr($userInfo->user_avatar,'helloo')!==false;
                break;
            case 'Background': // 个人背景
                $flag = !empty($userInfo->user_bg);
                break;
            case 'School': // 学校信息
                $flag = !empty($userInfo->user_sl) && stristr($userInfo->user_sl, 'others')===false;
                break;
            case 'Bio':  // 个性签名
                $flag = !empty($userInfo->user_about);
                break;
            case 'ID': // 专属ID
                $flag = stristr($userInfo->user_name, 'lb_')===false;
                break;
            case 'Used5Masks': // 百变大咖
                $flag = $statistic->props>=5;
                break;
            case 'Video being liked': // 人气之星
                $flag = $statistic->liked ?? false;
                break;
            case 'Liked others\' videos': // 海中霸主
                $flag = $statistic->like ?? false;
                break;
            case 'Msgpoint': // message
                $flag = $statistic->txt ?? false;
                break;
            case 'Videopoint': // video
                $flag = $statistic->video ?? false;
                break;
            case 'Add friends': // 交友达人
                $flag = $statistic->friend ?? false;
                break;
            case 'Post video': // Vlog Video
                $flag = $vlog;
                break;
            case 'Post photo': // Photo Wall
                $flag = $photo;
                break;
            case '10txt chats': // 文字战斗机
                $flag = $tenText;
                break;
            case '10video chats': // 视频创作者
                $flag = $tenVideo;
                break;
            case 'BronzeGamer': // 游戏小能手Ⅰ
                $flag = $statistic->game_score>=300;
                break;
            case 'SilverGamer': // 游戏小能手Ⅱ
                $flag = $statistic->game_score>=800;
                break;
            case 'GoldGamer': // 游戏小能手Ⅲ
                $flag = $statistic->game_score>=1500;
                break;
            case '10Friends': // 社交达人Ⅰ
                $flag = $statistic->friend>=10;
                break;
            case '30Friends': // 社交达人Ⅱ
                $flag = $statistic->friend>=30;
                break;
            case '100Friends': // 社交达人Ⅲ
                $flag = $statistic->friend>=100;
                break;
            case '300Msgs': // 妙语连珠Ⅰ
                $flag = $statistic->sent>=300;
                break;
            case '1000Msgs': // 妙语连珠Ⅱ
                $flag = $statistic->sent>=1000;
                break;
            case '3000Msgs': // 妙语连珠Ⅲ
                $flag = $statistic->sent>=3000;
                break;
            case 'Used50Masks': // 面具收集者
                $flag = $statistic->props>=50;
                break;
            case 'Friend from another school': // 交际爱好者
                $flag = !empty($statistic->other_school_friend);
                break;
            default:
                $flag = false;
                break;
        }
        return $flag;

    }

    /**
     * @param $num
     * @return mixed
     */
    public function top($num)
    {
        $num     = $num >=100 ? 100 : $num;
        $rank    = [2=>100, 3=>1234072139, 6=>1562134513, 7=>1402551869, 11=>2091996857, 23=>1885497935, 35=>1399005307];
        $tmpId   = array_values($rank);
        $memKey  = 'helloo:account:user-score-rank';
        $members = Redis::zrevrangebyscore($memKey, '+inf', '-inf', ['withScores'=>true, 'limit'=>[0,$num]]);

        foreach ($rank as $kk=>$vv) {
            $score       = intval(array_sum(array_slice($members, $kk-2, 2))/2);
            $first_array = array_slice($members, 0, $kk-1, true);
            $members     = $first_array + [$vv=>"$score"] + $members;
        }
        $members = array_slice($members, 0, 100, true);

        $uIds    = array_keys($members);
        $userIds = array_merge($uIds, $tmpId);
        $isExist = array_search($this->userId, $userIds);
        $users   = User::whereIn('user_id', $userIds)->select('user_id', 'user_name', 'user_nick_name', 'user_avatar')->get();
        $friends = UserFriend::where('user_id', $this->userId)->whereIn('friend_id', $userIds)->get();
        $request = UserFriendRequest::where('request_from_id', $this->userId)->whereIn('request_to_id', $userIds)->get();

        foreach ($users as $user) {
            $user->status = false;
            if ($isExist && $user->user_id==$this->userId) {
                $user->status='self';
            }
            foreach ($members as $key=>$score) {
                if ($user->user_id==$key) {
                    $user->score = $score;
                }
            }
            foreach ($friends as $friend) {
                if ($user->user_id==$friend->friend_id) {
                    $user->status = 'friend';
                }
            }
            if (empty($user->status)) {
                foreach ($request as $item) {
                    if ($user->user_id==$item->friend_id) {
                        $user->status = 'request';
                    }
                }
            }
        }

        $users = collect($users)->sortByDesc('score')->values();

        return $users;

    }

    /**
     * @param $num
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     * 朋友推荐
     */
    public function recommend($num)
    {
        if (empty((int)$num)) {
            return $this->response->errorBadRequest();
        }
        $num = $num >= 30 ? 30 : 10;
        $middle = $num/2;
        $memKey = 'helloo:account:user-recommend';
        $all    = UserFriend::where('user_id', $this->userId)->pluck('friend_id')->toArray(); // 所有的好友

        // 有共同好友
        $friendIds = $this->mutualFriend($num, $all, $memKey);
        $fCount    = count($friendIds);

        // 同校
        $all     = array_merge($all, $friendIds, [$this->userId]);
        $schools = $this->mutualSchool($num, $all, $memKey);
        $sCount  = count($schools);

        $finalFriend = $finalSchool = $finalCountry = [];
        // 同国家
        if (($fCount+$sCount) < $num) {
            $all     = array_merge($all, $schools);
            $country = $this->mutualCountry($num, $all, $memKey);
            $finalFriend = $friendIds;
            $finalSchool = $schools;
            $finalCountry= array_slice($country, $num-($fCount+$sCount));
        } else {
            if ($fCount >= $middle && $sCount >= $middle) {
              $finalFriend = array_slice($friendIds, 0, $middle);
              $finalSchool = array_slice($schools, 0, $middle);
            }
            if ($fCount >= $middle && $sCount < $middle) {
                $finalFriend = array_slice($friendIds, 0, $num-$sCount);
                $finalSchool = $schools;
            }
            if ($fCount < $middle && $sCount >= $middle) {
                $finalFriend = $friendIds;
                $finalSchool = array_slice($schools, 0, $num-$fCount);
            }
        }
        $ids  = array_merge($finalFriend, $finalSchool, $finalCountry);
        $users = User::whereIn('user_id', $ids)->select('user_id', 'user_name', 'user_nick_name', 'user_avatar')->get();
        foreach ($users as $user) {
            if (in_array($user->user_id, $finalFriend)) {
                $user->flag = 1;
            }
            if (empty($user->flag) && in_array($user->user_id, $finalSchool)) {
                $user->flag = 2;
            }
            if (empty($user->flag) && in_array($user->user_id, $finalCountry)) {
                $user->flag = 3;
            }
        }

        $users = collect($users)->sortBy('flag')->values();
        $users = collect($users)->map(function ($user) {
            $user->flag = $user->flag == 1 ? 'friend' : ($user->flag==2 ? 'school' : 'country');
            return $user;
        });
        return UserCollection::collection($users);
    }

    /**
     * @param $num
     * @param $all
     * @param $memKey
     * 同国家
     * @return array|mixed
     */
    public function mutualCountry($num, $all, $memKey)
    {
        $country = $this->user->user_country;
        if(empty($country)) {
            return [];
        }

        $users = Country::where('user_country', $country)->pluck('user_id')->toArray();
        return $this->diff($num, $all, $users);
    }

    /**
     * @param $num
     * @param $all
     * @param $memKey
     * 同校的
     * @return array
     */
    public function mutualSchool($num, $all, $memKey)
    {
        $school = $this->user->user_school;
        if(empty($school) && strtolower($this->user->user_school) == 'other') {
            return [];
        }

        $school = User::where('user_school', $school)->pluck('user_id')->toArray();
        return $this->diff($num, $all, $school);
    }

    /**
     * @param $num
     * @param $all
     * @param $memKey
     * @return mixed
     * 有共同好友的
     */
    public function mutualFriend($num, $all, $memKey)
    {
        $rand    = count($all) > $num ? $num : count($all);
        $friends = array_random($all, $rand);

        $list    = UserFriend::whereIn('user_id', $friends)->pluck('friend_id')->unique()->toArray();
        $users   = array_merge(array_diff($list, $all));

        return $this->diff($num, $all, $users);
    }

    /**
     * @param $num
     * @param $all
     * @param $users
     * @return mixed
     *
     */
    public function diff($num, $all, $users)
    {
        $all  = array_merge($all, [$this->userId]);
        $diff = array_diff($users, $all);
        $diff = collect($diff)->unique()->toArray();
        $rand = count($diff) > $num ? $num : count($diff);
        return array_random($diff, $rand);
    }
}
