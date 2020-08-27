<?php

namespace App\Http\Controllers\V1;

use App\Models\Es;
use App\Traits\CachablePost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Resources\UserSearchCollection;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use App\Resources\PostSearchPaginateCollection;
use App\Resources\TopicSearchPaginateCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class SearchController extends BaseController
{
    use CachablePost;

    private $searchPost;
    private $searchUser;
    private $searchTopic;

    public function __construct()
    {
        $this->searchPost  = config('scout.elasticsearch.post');
        $this->searchUser  = config('scout.elasticsearch.user');
        $this->searchTopic = config('scout.elasticsearch.topic');
    }

    public function index(Request $request)
    {
        $params = $request->all();

        if (empty($params['keyword'])) {
            return [];
        }
        //截取20个字符
        $params['keyword'] =  mb_str_limit(trim($request['keyword']), 20, null);
        $type   = $params['type'] ?? 0;

        switch ($type) {
            case 1: // 用户
                return $this->searchUser($params);
                break;
            case 2: // 帖子
                return $this->searchPost($params);
                break;
            case 3: // 话题
                return $this->searchTopic($params);
                break;
            /*case 4: // 输入中
                $result = $this->searchTopic($params);
                $result  = $result->additional(['user'=>$this->searchUser($params, 3)]);
                return $result;
                break;*/
            case 4:  // 输入中 ES suggest
                //$result = $this->searchTopicIng($params);
                $result = $this->searchPostIng($params);
                $result['user'] = $this->searchUserIng($params, 3);
                return $result;
                break;
            default: // 全部
                $result = $this->searchPost($params);
                if ((!empty($params['page']) && $params['page']==1) || empty($params['page'])) {
                    $res = [
                        'user'  => $this->searchUser($params),
                        'topic' => $this->searchTopic($params)
                    ];
                    $result = $result->additional($res);
                }
                return $result;
        }
    }

    /**
     * @return array
     * 热门话题
     */
    public function hotSearch()
    {
        $data = $this->getHotSearch();
        return ['data'=>$data];
        shuffle($data);
        foreach ($data as $value) {
            $result['data'][] = ['title' => $value];
        }
        return $result ?? [];
    }

    /**
     * @return array|mixed
     * 获取搜索热词
     */
    protected function getHotSearch()
    {
        /** @var Redis $key */
        $key  = 'hotSearch';
        $list = Redis::get($key);
        return $list;

        if (empty($list)) {
            $data = ['开心', 'hello', 'yooul', '你好', '谈恋爱', '找朋友', '中国','美女', '帅哥', '可口可乐'];
            Redis::set($key, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        $list = Redis::get($key);
        return !empty($list) ? json_decode($list, true) : [];

    }

    /**
     * @param $params
     * @param int $limit
     * @return AnonymousResourceCollection
     * 查询用户
     */
    protected function searchUser($params, $limit=10)
    {
        $user = (new Es($this->searchUser, ['limit'=>$limit]))->likeQuery($params);
        $user = $user->appends($params);
        return UserSearchCollection::collection($user);
    }

    protected function searchUserIng($params, $limit=10)
    {
        $user = (new Es($this->searchUser, ['limit'=>$limit]))->suggest($params);
        $user = !empty($user) ? array_slice($user, 0, 3) : [];
        return UserSearchCollection::collection(collect($user));
    }

    protected function searchPostIng($params, $limit=10)
    {
        $filter = ['mustNot'=>['post_is_delete'], 'limit'=>$limit];
        $result = (new Es($this->searchPost, $filter))->suggest($params);
        return ['data'=> $result];
    }

    /**
     * @param $params
     * @param int $limit
     * @return AnonymousResourceCollection
     * 搜索帖子
     */
    protected function searchPost($params, $limit=10) {
        $filter      = ['term'=>['post_locale'=>locale()], 'mustNot'=>['post_is_delete'], 'limit'=>$limit];
        $posts       = (new Es($this->searchPost, $filter))->likeQuery($params);

        $userIds     = $posts->pluck('user_id')->all();
        $postIds     = $posts->pluck('post_id')->all();
        $users       = app(UserRepository::class)->findByMany($userIds);
        $postLikes   = app(PostRepository::class)->userPostLike($postIds);
        $postDisLikes= app(PostRepository::class)->userPostDislike($postIds);

        foreach ($posts as $index=>$post) {
            $post['owner'] = $users->where('user_id' , $post['user_id'])->first();
            $post['likeState'] = in_array($post['post_id'] , $postLikes);
            $post['dislikeState'] = in_array($post['post_id'] , $postDisLikes);
            $post['post_comment_num'] = $this->commentCount($post['post_id']);
            $post['post_event_country'] = getCountryName($post['post_event_country_id']);
            $posts[$index] = $post;
        }
        $posts = $posts->appends($params);
        return PostSearchPaginateCollection::collection($posts);

    }

    /**
     * @param $params
     * @param int $limit
     * @return AnonymousResourceCollection
     * 查询topic
     */
    protected function searchTopic($params, $limit=10)
    {
        $topic = (new Es($this->searchTopic, ['limit'=>$limit]))->likeQuery($params);
        $topic = $topic->appends($params);
        return TopicSearchPaginateCollection::collection($topic);
    }

    protected function searchTopicIng($params, $limit=10)
    {
        $topic = (new Es($this->searchTopic, ['limit'=>$limit]))->suggest($params);
        return ['data' => $topic];
    }

}
