<?php

namespace App\Http\Controllers\V1;

use App\Models\Es;
use App\Traits\CachablePost;
use Illuminate\Http\Request;
use App\Resources\UserSearchCollection;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use App\Resources\PostSearchPaginateCollection;
use App\Resources\TopicSearchPaginateCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $params['keyword'] = mb_str_limit(trim($request['keyword']), 20, null);
        $params['page']    = $params['page'] ?? 1;
        $type   = $params['type'] ?? 0;
        switch ($type) {
            case 1: // 用户
                $result = $this->searchUser($params);
                break;
            case 2: // 帖子
                $result = $this->searchPost($params);
                break;
            case 3: // 话题
                $result = $this->searchTopic($params);
                break;
            case 4:  // 输入中 ES suggest
                $result = $this->searchTopicIng($params);
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
        }

        $data        = $result->items();
        $resultUser  = !empty($result->additional['user'])  ? $result->additional['user']->resource->items()  : [];
        $resultTopic = !empty($result->additional['topic']) ? $result->additional['topic']->resource->items() : [];
        $resultPost  = !empty($result->additional['post'])  ? $result->additional['post']->resource->items()  : [];

        //查询出数据时，搜索入库
        if (!empty($data) || !empty($resultUser) || !empty($resultTopic) || !empty($resultPost)) {
            $this->history($params);
        }
        return $result;
    }

    /**
     * 插入搜索记录
     * @param $params
     */
    protected function history($params)
    {
        try {
            if (auth()->check()) {
                $userId  = auth()->user()->user_id;
                $key     = "search_".$userId;
                $today   = strtotime(date('Y-m-d'));
                $keyword = mb_convert_case($params['keyword'], MB_CASE_LOWER, "UTF-8");
                $value   = Redis::zscore($key, $keyword);
                if (empty($value) || $value != $today) {
                    Redis::zremrangebyscore($key, 0, $today-1); // 删除小于今天的数据
                    Redis::zadd($key, $today, $keyword);
                    DB::insert('insert into f_search_history(user_id, title, created_at) values (?, ?, ?)', [$userId, $keyword, time()]);
                }
            }
        } catch (\Exception $e) {
            Log::error(__FUNCTION__.' Exception: code:'.$e->getCode(). ' message:'.$e->getMessage());
        }
    }

    /**
     * @return array
     * 热门搜索 主体
     */
    public function hotSearch()
    {
        $hot     = $this->getHotSearch();
        $history = $this->getSearchHistory();
        $data    = array_unique(array_merge($hot, $history));
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
        $key     = 'hot_search';
        $list    = Redis::get($key);
        $hot     = !empty($list) ? json_decode($list, true) : [];
        if (count($hot) != count($hot, 1)) {
            $hot = array_column($hot, 'title');
        }
        return $hot;
    }

    /**
     * @return array|mixed
     * 查询搜索历史 先查缓存 后数据库
     */
    protected function getSearchHistory()
    {
        $key        = 'hot_search_history';
        $result     = Redis::get($key);
        $expireTime = 60*60*6*6;
        $time       = time() - $expireTime;
        if (empty($result)) {
            $result = DB::select('SELECT count(1) num, title from f_search_history where created_at > ? GROUP by title ORDER BY num desc limit 5', [$time]);
            $result = $result ? array_column($result, 'title') : [];
            $result && Redis::set($key, json_encode($result, JSON_UNESCAPED_UNICODE), "nx", "ex", $expireTime);
        } else {
            $result = json_decode($result, true);
        }

        return $result ?? [];
    }

    /**
     * @param $params
     * @param int $limit
     * @return AnonymousResourceCollection
     * 查询用户
     */
    protected function searchUser($params, $limit=10)
    {
        $user = (new Es($this->searchUser, ['limit'=>$limit]))->likeQuery($params, true);
        $user = $user->appends($params);
        return UserSearchCollection::collection($user);
    }

    protected function searchUserIng($params, $limit=10)
    {
        $user = (new Es($this->searchUser, ['limit'=>$limit]))->suggest($params);
        return !empty($user) ? array_slice($user, 0, $limit) : [];
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
    protected function searchPost($params, $limit=10)
    {
        $filter      = ['mustNot'=>['post_is_delete'=>1], 'limit'=>$limit];
        $posts       = (new Es($this->searchPost, $filter))->likeQuery($params, true);
        $userIds     = $posts->pluck('user_id')->all();
        $postIds     = $posts->pluck('post_id')->all();
        $users       = app(UserRepository::class)->findByMany($userIds);
        $postLikes   = app(PostRepository::class)->userPostLike($postIds);
        $postDisLikes= app(PostRepository::class)->userPostDislike($postIds);
        $posts       = app(PostRepository::class)->voteList($posts);

        $authUser    = auth()->check() ? auth()->user()->user_id : 0;
        $uids        = app(PostRepository::class)->userFollowList($authUser, $userIds)->toArray();

        // 可见范围
        $posts = $posts->setCollection($posts->getCollection()->filter(function($post) use ($authUser, $uids) {
            if (empty($post['show_type']) || $post['show_type'] ==1) return true;

            if ($post['show_type']==2 && in_array($post['user_id'], $uids ?? [])) {
                return true;
            }
            if ($post['show_type']==3 && ($post['user_id'] == $authUser)) {
                return true;
            }
        })->values());

        //重试3次
        if (empty($posts->items())) {
            $params['page']++;
            if ($params['page']<=3) {
                return $this->searchPost($params);
            }
        }

        foreach ($posts as $index=>$post) {
            $post['owner'] = $users->where('user_id' , $post['user_id'])->first();
            $post['likeState'] = in_array($post['post_id'] , $postLikes);
            $post['dislikeState'] = in_array($post['post_id'] , $postDisLikes);
            $post['post_comment_num'] = $this->commentCount($post['post_id']);
            $post['post_event_country'] = getPostCountryName($post['post_event_country_id']);
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
        $topic = (new Es($this->searchTopic, ['limit'=>$limit]))->likeQuery($params, true);
        $topic = $topic->appends($params);
        return TopicSearchPaginateCollection::collection($topic);
    }

    protected function searchTopicIng($params, $limit=10)
    {
        $topic = (new Es($this->searchTopic, ['limit'=>$limit]))->suggest($params);
        return ['data' => $topic];
    }

}
