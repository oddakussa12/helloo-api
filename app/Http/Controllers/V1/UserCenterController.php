<?php

namespace App\Http\Controllers\V1;

use App\Models\LikePhoto;
use App\Models\LikeVideo;
use App\Models\Photo;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserCenterController extends BaseController
{
    private $user;
    private $userId;

    public function __construct()
    {
        $this->user   = auth()->user();
       // $this->userId = auth()->id();
        $this->userId = 1;
    }

    /**
     * @return array
     * 获取照片、视频列表
     */
    public function getMedia()
    {
        $result['video'] = $this->getVideos();
        $result['photo'] = $this->getPhotos();
        return $result;
    }

    /**
     * @return mixed
     * 获取Vlog Videos
     */
    public function getVideos()
    {
        $videos = Video::select('video_id', 'image', 'like', 'video_url')->where('user_id', $this->userId)->orderByDesc('created_at')->limit(10)->get();
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
     * @return mixed
     * 获取照片墙
     */
    public function getPhotos()
    {
        $photos = Photo::select('photo_id', 'photo', 'like')->where('user_id', $this->userId)->orderByDesc('created_at')->limit(10)->get();
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
            return $this->response->error('超出上限，最多十条');
        }

        $data['user_id'] = $this->userId;
        $data[$image] = $params['image'];

        if ($params['type'] =='video') {
            $data['video_url'] = $params['video_url'];
        }

        $model->create($data);
        return $this->response->accepted();
    }

//    public function destroyVideo($id)
//    {
//        $this->destroyMedia('video', $id);
//        return $this->response->accepted();
//    }
//    public function destroyPhoto($id)
//    {
//        $this->destroyMedia('photo', $id);
//        return $this->response->accepted();
//    }

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
     * 获取隐私配置
     */
    public function privacy()
    {
        $result = DB::table('users_setting')->where('user_id', $this->userId)->first();
        if (empty($result)) {
            $result['user_id']    = $this->userId;
            $result['friend']     = 1;
            $result['video']      = 1;
            $result['photo']      = 1;
            $result['created_at'] = date('Y-m-d H:i:s');
            DB::table('users_setting')->insert($result);
        }

        return collect($result)->only('friend', 'video', 'photo');
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 修改隐私配置
     */
    public function updatePrivacy(Request $request)
    {
        $this->validate($request, [
            'friend' => 'required|string',
            'video' => 'required|string',
            'photo' => 'required|string',
        ]);
        $params = $request->only('friend', 'video', 'photo');
        $params['updated_at'] = date('Y-m-d H:i:s');
        DB::table('users_setting')->where('user_id', $this->userId)->update($params);
        return $this->response->accepted();
    }


}
