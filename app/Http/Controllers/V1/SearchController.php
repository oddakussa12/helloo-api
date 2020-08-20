<?php

namespace App\Http\Controllers\V1;

use App\Models\Es;
use App\Resources\PostPaginateCollection;
use App\Resources\PostSearchPaginateCollection;
use App\Resources\SearchPaginateCollection;
use App\Resources\TopicPaginateCollection;
use App\Resources\TopicSearchPaginateCollection;
use App\Resources\UserSearchCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;


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
               $result  = $result->additional(['user'=>$this->searchUser($params)]);
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
        for ($i=1; $i<=10; $i++) {
            $data[] = ['post_content' => '热门话题'.$i];
        }

        return $data ?? [];

    }
    /**
     * @param $params
     * @return AnonymousResourceCollection
     * 查询用户
     */
    protected function searchUser($params) {
        $likeColumns = ['user_nick_name', 'user_name'];
        $user = (new Es('user'))->likeQuery($params, $likeColumns);
        return UserSearchCollection::collection($user);
    }

    /**
     * @param $params
     * @return AnonymousResourceCollection
     * 搜索帖子
     */
    protected function searchPost($params) {
        $likeColumns = ['post_content'];
        $filter      = ['post_content_default_locale'=>locale()];
        $post        = (new Es('post', $filter))->likeQuery($params, $likeColumns);

        return PostSearchPaginateCollection::collection($post);
    }

    /**
     * @param $params
     * @return AnonymousResourceCollection
     * 查询topic
     */
    protected function searchTopic($params)
    {
        $likeColumns = ['topic_content'];
        $topic = (new Es('topic'))->likeQuery($params, $likeColumns);
        return TopicSearchPaginateCollection::collection($topic);
    }

}
