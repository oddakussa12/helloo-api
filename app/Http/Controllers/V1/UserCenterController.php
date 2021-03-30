<?php

namespace App\Http\Controllers\V1;

use App\Models\LikePhoto;
use App\Models\LikeVideo;
use App\Models\Photo;
use App\Models\UserFriend;
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
            $video  = $photo = $friend = true;
            $friend = $this->userId;
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
        if (empty($params['type']) || !in_array($params['type'], ['video', 'photo'])) {
            return $this->response->error('type error', 400);
        }
        $valid  = [
            'image' => 'required|string|min:40',
            'video_url' => 'required|string|min:40'
        ];
        $this->validate($request, $valid);

        $model = $params['type'] == 'video' ? new Video() : new Photo();
        $image = $params['type'] == 'video' ? 'image' : 'photo';
        $count = $model->where('user_id', $this->userId)->count();
        if ($count>=10) {
            return $this->response->errorNotFound('超出上限，最多十条');
        }

        $data['user_id'] = $this->userId;
        $data[$image] = $params['image'];

        if ($params['type'] =='video') {
            $data['video_url']   = $params['video_url'];
            $data['bundle_name'] = $params['mask'] ?? '';
        }

        $create = $model->create($data);
        if (!empty($create->getKey())) {
            $score['sourceType'] = $params['type'];
            $score['type'] = 'addMedia';
            $score['user_id'] = $this->userId;
            $score['id'] = $create->getKey();
            $this->addScore($score);
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
    public function likes(Request $request)
    {
        $type  = $request->input('type', '');
        $id    = $request->input('id', 0);
        if (empty($type) || empty($id)) {
            return $this->response->errorNotFound();
        }

        $model = $type == 'video' ? new Video() : new Photo();
        $row   = $model->find($id);
        if (empty($row)) {
            return $this->response->errorNotFound();
        }

        $like  = $type == 'video' ? new LikeVideo() : new LikePhoto();
        $check = $like->where(['user_id'=>$this->userId, 'liked_id'=>$id])->first();
        if (empty($check)) {
            $data['user_id'] = $this->userId;
            $data['liked_id'] = $id;
            $data['created_at'] = date('Y-m-d H:i:s');
            $result = $like->insert($data);
            if ($result) {
                $score['sourceType'] = $type;
                $score['type'] = 'like';
                $score['user_id'] = $this->userId;
                $score['friend_id'] = $row['user_id'];
                $score['id'] = $id;
                $this->addScore($score);
            }
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
        $result = DB::table('medals')->select('id', 'name', 'desc', 'image', 'sort', 'category')->get();
        $medals = [];
        $categories = $result->pluck('category')->unique()->toArray();

        $friends = UserFriend::where('user_id', $this->userId)->count();
        $game    = DB::table('users_games')->whereIn('user_id', $this->userId)->orderByDesc('score')->orderBy('created_at')->first();
        $game    = !empty($game) ? $game['score'] : 0;


        foreach ($result as $item) {
            foreach ($categories as $category) {
                if ($category==$item->category) {
                    $name = json_decode($item->name, true);
                    $desc = json_decode($item->desc, true);
                    $item->name = $name[$locale];
                    $item->desc = $desc[$locale];
                    $this->status($item, $friends, $game);

                    $medals[$category][] = $item;
                }
            }
       }
        return $medals;
    }

    /**
     * @param $media
     * @param $friends
     * @param $game
     * @return bool
     * 奖章状态
     */
    public function status($media, $friends, $game)
    {
        $info = $this->user;
        switch ($media->name) {
            case 'Profile picture': // 个人头像
                $flag = stripos($info->user_avatar,'helloo')!==false;
                break;
            case 'Background': // 个人背景
                $flag = !empty($info->user_bg);
                break;
            case 'School': // 学校信息
                $flag = stripos($info->user_school, 'other')!==false;
                break;
            case 'Bio':  // 个性签名
                $flag = !empty($info->user_about);
                break;
            case 'ID': // 专属ID
                $flag = stripos($info->user, 'lb_')!==false;
                break;
            case 'Used5Masks': // 百变大咖
                $flag = $this->usedMasks($this->userId, 5);
                break;
            case 'Video being liked': // 人气之星
                $flag = $this->scoreStatistics($this->userId);
                break;
            case 'Liked others\' videos': // 海中霸主
                $flag = true;
                break;
            case 'Msgpoint': // message
                $flag = true;
                break;
            case 'Videopoint': // video
                $flag = true;
                break;
            case 'Add friends': // 交友达人
                $flag= true;
                break;
            case 'Post video': // Vlog Video
                $flag= true;
                break;
            case 'Post photo': // Photo Wall
                $flag= true;
                break;
            case '10txt chats': // 文字战斗机
                $flag= true;
                break;
            case '10video chats': // 视频创作者
                $flag= true;
                break;
            case 'BronzeGamer': // 游戏小能手Ⅰ
                $flag= $game>=300;
                break;
            case 'SilverGamer': // 游戏小能手Ⅱ
                $flag= $game>=800;
                break;
            case 'GoldGamer': // 游戏小能手Ⅲ
                $flag= $game>=1500;
                break;
            case '10Friends': // 社交达人Ⅰ
                $flag= $friends>=10;
                break;
            case '30Friends': // 社交达人Ⅱ
                $flag= $friends>=30;
                break;
            case '100Friends': // 社交达人Ⅲ
                $flag= $friends>=100;
                break;
            case '300Msgs': // 妙语连珠Ⅰ
                $flag= $this->message($this->userId, 300);
                break;
            case '1000Msgs': // 妙语连珠Ⅱ
                $flag= $this->message($this->userId, 1000);
                break;
            case '3000Msgs': // 妙语连珠Ⅲ
                $flag= $this->message($this->userId, 3000);
                break;
            case 'Used50Masks': // 面具收集者
                $flag= $this->usedMasks($this->userId);
                break;
            case 'Friend from another school': // 交际爱好者
                $flag= $this->message($this->userId, 3000);
                break;
            default:
                break;
        }
        return $flag;

    }

    public function message($userId, $num)
    {
        return true;
        
    }

    /**
     * @param $userId
     * @return bool
     * 查询统计表
     */
    public function scoreStatistics($userId)
    {
        // DB::table('')->where()->first();
        return true;
    }

    public function usedMasks($userId, $num=5)
    {
        $count = DB::table('users_videos')->where('user_id', $userId)->where('bundle_name', '!=', '')->count();
        if ($count>=$num) {
            return true;
        }
    }

}
