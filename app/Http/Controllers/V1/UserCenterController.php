<?php

namespace App\Http\Controllers\V1;

use App\Jobs\MoreTimeUserScoreUpdate;
use App\Models\LikePhoto;
use App\Models\LikeVideo;
use App\Models\Photo;
use App\Models\RyMessageCount;
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
use Ramsey\Uuid\Uuid;

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
        // 查询点赞表
        $likes = LikeVideo::where('user_id', $this->userId)->whereIn('liked_id', $videoIds);
        foreach ($videos as $video) {
            $video->isLiked = false;
            foreach ($likes as $like) {
                if ($like->liked_id==$video->video_id) {
                    $like->isLiked = true;
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
        // 查询点赞表
        $likes = LikePhoto::where('user_id', $this->userId)->whereIn('liked_id', $photoIds);
        foreach ($photos as $photo) {
            $photo->isLiked = false;
            foreach ($likes as $like) {
                if ($like->liked_id==$photo->photo_id) {
                    $like->isLiked = true;
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
            return $this->response->errorNotFound('超出上限，最多十条');
        }
        if (count($images)+$count>10) {
            return $this->response->errorNotFound('超出上限，最多十条，当前已有'.$count.'条');
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
            Redis::expire($mKey , 86400*30);
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
            'type' => [
                'required',
                Rule::in(array('video' , 'photo'))
            ],
            'id' => [
                'required',
            ],
        ];
        $type  = $request->input('type', '');
        $id    = $request->input('id', 0);
        $params = array('type'=>$type , 'id'=>$id);
        Validator::make($params, $rules)->validate();
        $time = date('Y-m-d H:i:s');
        $model = $type == 'video' ? new Video() : new Photo();
        $model = $model->findOrFail($id);
        $like  = $type == 'video' ? new LikeVideo() : new LikePhoto();
        $check = $like->where(['user_id'=>$this->userId, 'liked_id'=>$id])->first();
        if (empty($check)) {
            $snowId =  app('snowflake')->id();
            $data['id'] = $snowId;
            $data['user_id'] = $this->userId;
            $data['liked_id'] = $id;
            $data['created_at'] = $time;
            $like->create($data);

            $likeCount = DB::table('ry_messages_counts')->where('user_id' , $this->userId)->first();
            if(blank($likeCount))
            {
                DB::table('ry_messages_counts')->insert(array(
                    'user_id'=>$this->userId,
                    'like_video'=>1,
                    'created_at'=>$time,
                    'updated_at'=>$time,
                ));
            }else{
                DB::table('ry_messages_counts')->where('user_id' , $model->user_id)->increment('like' , 1 , array(
                    'updated_at'=>$time,
                ));
            }
            MoreTimeUserScoreUpdate::dispatch($this->userId , 'likeVideo' , $snowId)->onQueue('helloo_{more_time_user_score_update}');
            $likedCount = DB::table('ry_messages_counts')->where('user_id' , $model->user_id)->first();
            if(blank($likedCount))
            {
                DB::table('ry_messages_counts')->insert(array(
                    'user_id'=>$model->user_id,
                    'liked_video'=>1,
                    'created_at'=>$time,
                    'updated_at'=>$time,
                ));
            }else{
                DB::table('ry_messages_counts')->where('user_id' , $model->user_id)->increment('liked' , 1 , array(
                    'updated_at'=>$time,
                ));
            }
            MoreTimeUserScoreUpdate::dispatch($model->user_id , 'likedVideo' , $snowId)->onQueue('helloo_{more_time_user_score_update}');
        }
       return $this->response->accepted();
    }

    /**
     * 获取勋章列表
     */
    public function medal()
    {
        $locale = locale();
        $locale = $locale == 'zh-CN' ? 'cn' : 'en';
        $result = DB::table('medals')->select('id', 'title', 'name', 'desc','image', 'score', 'category')->get();
        $medals = [];
        $day    = date('Y-m-d');

        $vlog   = DB::table('users_videos')->where('user_id', $this->userId)->get()->count();
        $photo  = DB::table('users_photos')->where('user_id', $this->userId)->count();
        $statistic = RyMessageCount::where('user_id', $this->userId)->first();
        $statistic = !empty($statistic) ? $statistic : new RyMessageCount();

        $mKey       = "helloo:message:service:mutual-video-geq-ten".$day;
        $memKey     = "helloo:message:service:mutual-txt-geq-ten".$day;

        $tenText    = Redis::sismember($memKey, $this->userId);
        $tenVideo   = Redis::sismember($mKey, $this->userId);
        $categories = $result->pluck('category')->unique()->toArray();
        foreach ($result as $item) {
            foreach ($categories as $category) {
                if ($category==$item->category) {
                    $name = json_decode($item->name, true);
                    $desc = json_decode($item->desc, true);
                    $item->name = $name[$locale];
                    $item->desc = $desc[$locale];
                    $item->flag = $this->status($item, $statistic, $vlog, $photo, $tenVideo, $tenText);

                    $medals[$category][] = $item;
                }
            }
       }
        return $medals;
    }

    /**
     * @param $media
     * @param $statistic
     * @param $vlog
     * @param $photo
     * @param $tenVideo
     * @param $tenText
     * @return bool
     * 奖章状态
     */
    public function status($media, $statistic, $vlog, $photo, $tenVideo, $tenText)
    {
        $info = $this->user;
        switch (trim($media->title)) {
            case 'Profile picture': // 个人头像
                $flag = stristr($info->user_avatar,'helloo')!==false;
                break;
            case 'Background': // 个人背景
                $flag = !empty($info->user_bg);
                break;
            case 'School': // 学校信息
                $flag = stristr($info->user_school, 'other')===false;
                break;
            case 'Bio':  // 个性签名
                $flag = !empty($info->user_about);
                break;
            case 'ID': // 专属ID
                $flag = stristr($info->user_name, 'lb_')===false;
                break;
            case 'Used5Masks': // 百变大咖
                $flag = $statistic->props>=5;
                break;
            case 'Video being liked': // 人气之星
                $flag = $statistic->liked_video;
                break;
            case 'Liked others\' videos': // 海中霸主
                $flag = $statistic->like_video;
                break;
            case 'Msgpoint': // message
                $flag = $statistic->txt;
                break;
            case 'Videopoint': // video
                $flag = $statistic->video;
                break;
            case 'Add friends': // 交友达人
                $flag = $statistic->friend;
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
                $flag = $statistic->message>=300;
                break;
            case '1000Msgs': // 妙语连珠Ⅱ
                $flag = $statistic->message>=1000;
                break;
            case '3000Msgs': // 妙语连珠Ⅲ
                $flag = $statistic->message>=3000;
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
        $memKey  = 'helloo:account:user-score-rank';
        $members = Redis::zrevrangebyscore($memKey, '+inf', '-inf', ['withScores'=>true, 'limit'=>[0,$num]]);
        $userIds = array_keys($members);
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

}
