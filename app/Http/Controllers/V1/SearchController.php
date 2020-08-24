<?php

namespace App\Http\Controllers\V1;

use App\Models\Es;
use App\Repositories\Contracts\UserRepository;
use App\Resources\PostSearchPaginateCollection;
use App\Resources\TopicSearchPaginateCollection;
use App\Resources\UserSearchCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;


class SearchController extends BaseController
{
    private $searchPost;
    private $searchUser;
    private $searchTopic;

    public function __construct()
    {
        $this->searchPost = config('scout.elasticsearch.post');
        $this->searchUser = config('scout.elasticsearch.user');
        $this->searchTopic = config('scout.elasticsearch.topic');

    }

    public function index(Request $request)
    {
        $params = $request->all();
        $type   = $params['type'] ?? 0;

        if (empty($params['keyword'])) {
            return [];
        }

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
            case 4: // 输入中
                $result = $this->searchTopic($params);
                $result  = $result->additional(['user'=>$this->searchUser($params, 3)]);
                return $result;
                break;
            case 5:
                $result = $this->searchUserIng($params, 3);
//                $result = $this->searchPostIng($params, 3);
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
    public function hotTopic()
    {
        $topic = ['这是一个话题', 'hello', 'yooul', '你好', '谈恋爱', '找朋友', '中国','美女', '帅哥', '可口可乐'];
        shuffle($topic);
        foreach ($topic as $value) {
            $data['data'][] = ['topic_content' => $value];
        }
        return $data ?? [];
    }

    /**
     * @param $params
     * @param int $limit
     * @return AnonymousResourceCollection
     * 查询用户
     */
    protected function searchUser($params, $limit=10) {
        $extra = [
            'limit'      => $limit,
            'likeColumns'=> ['user_nick_name', 'user_name']
        ];
        $user = (new Es($this->searchUser, $extra))->likeQuery($params);
        return UserSearchCollection::collection($user);
    }

    protected function searchUserIng($params, $limit=10)
    {
        $extra = [
            'limit'      => $limit,
            'likeColumns'=> ['user_nick_name']
        ];
        $user = (new Es($this->searchUser, $extra))->suggest($params);
        return $user;

    }

    protected function searchPostIng($params, $limit=10)
    {
        $extra = [
            'limit'      => $limit,
            'likeColumns'=> ['post_content']
        ];
        $result = (new Es($this->searchPost, $extra))->suggest($params);
        return ['post' => $result];
    }

    /**
     * @param $params
     * @param int $limit
     * @return AnonymousResourceCollection
     * 搜索帖子
     */
    protected function searchPost($params, $limit=10) {
        $likeColumns = ['post_content'];
        $filter      = ['post_content_default_locale'=>locale(), 'limit'=>$limit];
        $posts       = (new Es($this->searchPost, $filter))->likeQuery($params, $likeColumns);

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
            $post['post_event_country'] = 'en';
            $posts[$index] = $post;
        }
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
        $extra = [
            'limit'      => $limit,
            'likeColumns'=> ['topic_content'],
        ];
        $topic = (new Es($this->searchTopic, $extra))->likeQuery($params);
        return TopicSearchPaginateCollection::collection($topic);
    }

}
