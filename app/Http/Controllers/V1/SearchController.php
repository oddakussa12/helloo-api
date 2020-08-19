<?php

namespace App\Http\Controllers\V1;

use App\Models\Es;
use App\Resources\PostPaginateCollection;
use App\Resources\SearchPaginateCollection;
use App\Resources\TopicPaginateCollection;
use App\Resources\UserSearchCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;


class SearchController extends BaseController
{

    public function index(Request $request)
    {
        $params = $request->all();
        switch ($params['type']) {
            case 1:
                return $this->searchUser($params);
                break;
            case 2:
                return $this->searchPost($params);
                break;
            case 3:
                return $this->searchTopic($params);
                break;
            default:
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
        $post = (new Es('post'))->likeQuery($params, $likeColumns);
        return PostPaginateCollection::collection($post);
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
        return TopicPaginateCollection::collection($topic);
    }


}
