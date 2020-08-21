<?php

namespace App\Http\Controllers\V1;

use App\Custom\RedisList;
use App\Resources\TopicSearchPaginateCollection;
use Illuminate\Http\Request;
use App\Resources\PostCollection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Database\Concerns\BuildsQueries;

class TopicController extends BaseController
{
    use BuildsQueries;

    public function index()
    {

    }

    public function myFollow(Request $request)
    {
        $pageName = 'topic_page';
        $perPage = 10;
        $user = auth()->user();
        $redis = new RedisList();
        $key = 'user.'.$user->user_id.'.'.'follow.topics';
        $page = intval($request->input($pageName, 1));
        $offset = ($page-1)*$perPage;
        if($redis->existsKey($key))
        {
           $total = $redis->zSize($key);
           $topics = $redis->zRevRangeByScore($key , '+inf' , '-inf' , true , array($offset , $perPage));
           $topics = array_keys($topics);
        }else{
           $total = 0;
           $topics = array();
        }
        $topics = collect($topics)->map(function ($item, $key) {
           return array('topic_content'=>$item);
        });
        $topics = $this->paginator($topics, $total , $perPage, $page , [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
        return TopicSearchPaginateCollection::collection($topics);
    }

    public function follow($topic)
    {
        $user = auth()->user();
        $key = 'user.'.$user->user_id.'.'.'follow.topics';
        if(Redis::zscore($key , $topic)===false)
        {
           Redis::zadd($key , time() , $topic);
        }
        return $this->response->noContent();
    }

    public function post($topic)
    {
        $posts = app(PostRepository::class)->paginateTopic($topic);
        return PostCollection::collection($posts);
    }

    public function hot()
    {
        $hotTopics = 'hot_topic';
        $redis = new RedisList();
        $topics = $redis->zRevRangeByScore($hotTopics , '+inf' , '-inf' , true);
        $topics = array_keys($topics);
        $topics = collect($topics)->map(function ($item, $key) {
           return array('topic_content'=>$item);
        });
        return TopicSearchPaginateCollection::collection($topics);
    }
}
