<?php

namespace App\Http\Controllers\V1;

use App\Models\Es;
use Illuminate\Http\Request;
use App\Resources\UserSearchCollection;
use App\Resources\TopicPaginateCollection;
use App\Resources\SearchPaginateCollection;
use App\Repositories\Contracts\UserRepository;
use App\Resources\PostSearchPaginateCollection;
use App\Resources\TopicSearchPaginateCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


class SearchController extends BaseController
{

    public function index(Request $request)
    {
        $params = $request->all();
        $type   = $params['type'] ?? 0;

        if (empty(trim($params['keyword']))) {
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
            $data['data'][] = ['post_content' => $value];
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
        $likeColumns = ['user_nick_name', 'user_name'];
        $user        = (new Es('user', ['limit'=>$limit]))->likeQuery($params, $likeColumns);
        return UserSearchCollection::collection($user);
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
        $posts        = (new Es('post', $filter))->likeQuery($params, $likeColumns);
        $userIds = $posts->pluck('user_id')->all();
        $users = app(UserRepository::class)->findByMany($userIds);
        $posts->each(function($post , $index) use ($users){
            $post->owner = $users->where('user_id' , $post->user_id)->first();
        });
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
        $likeColumns = ['topic_content'];
        $topic       = (new Es('topic', ['limit'=>$limit]))->likeQuery($params, $likeColumns);
        return TopicSearchPaginateCollection::collection($topic);
    }

}
