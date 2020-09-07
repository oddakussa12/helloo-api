<?php

namespace App\Http\Controllers\V1;

use App\Custom\RedisList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use App\Resources\PostPaginateCollection;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Database\Concerns\BuildsQueries;
use App\Resources\TopicSearchPaginateCollection;

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
        $topicNewKey = config('redis-key.topic.topic_index_new');
        if(Redis::zscore($key , $topic)===null&&Redis::zscore($topicNewKey , $topic)!==null)
        {
            $follow = DB::table('topics_follows')->where('user_id' , $user->user_id)->where('topic_content' , $topic)->first();
            if(empty($follow))
            {
                $time = time();
                DB::table('topics_follows')->insert(
                    array(
                        'user_id'=>$user->user_id,
                        'topic_content'=>$topic,
                        'created_at'=>$time,
                    )
                );
                Redis::zadd($key , $time , $topic);
            }
        }
        return $this->response->noContent();
    }

    public function unFollow($topic)
    {
        $user = auth()->user();
        $key = 'user.'.$user->user_id.'.'.'follow.topics';
        $topicNewKey = config('redis-key.topic.topic_index_new');
        if(Redis::zscore($key , $topic)!==null&&Redis::zscore($topicNewKey , $topic)!==null)
        {
            DB::table('topics_follows')->where('user_id' , $user->user_id)->where('topic_content' , $topic)->delete();
            Redis::zrem($key , $topic);
        }
        return $this->response->noContent();
    }

    public function post($topic)
    {
        $page = intval(request()->input('post_page' , 1));
        $posts = app(PostRepository::class)->paginateTopic($topic);
        $posts = PostPaginateCollection::collection($posts);
        $topicPostCountKey = config('redis-key.topic.topic_post_count');
        $count = Redis::zscore($topicPostCountKey , $topic);
        $additional = array(
            'discussCount'=>intval($count)
        );
        $user_follow_state = false;
        if(auth()->check())
        {
            $user = auth()->user();
            $key = 'user.'.$user->user_id.'.'.'follow.topics';
            if(Redis::zscore($key , $topic)!==null)
            {
                $user_follow_state = true;
            }
            $additional['user_follow_state'] = $user_follow_state;
        }
        if($page==1)
        {
            $posts = $posts->additional($additional);
        }
        return $posts;
    }

    /**
     * @return AnonymousResourceCollection
     * 热门话题
     */
    public function hot()
    {
        $key    = 'hot_topic';
        $result = Redis::get($key);
        $time   = Carbon::now();
        if(empty($result)) {
            $topics = DB::select('SELECT topic_content,flag,sort from f_hot_topics where is_delete<1 and (start_time > ? and end_time < ?) GROUP by topic_content ORDER BY flag asc sort desc limit 20', [$time, $time]);
            $result = sortArrByManyField($topics,'flag',SORT_ASC,'sort',SORT_DESC);
            $result = json_encode($result, JSON_UNESCAPED_UNICODE);
            Redis::set($key, $result);
        }

        $result = $result ? json_decode($result, true) : [];
        $result = array_map(function($v){unset($v['sort']);return $v;}, $result);

        $hotDb  = $this->getHotByDb();
        $result = array_merge($result, $hotDb);

        return TopicSearchPaginateCollection::collection(collect($result));
    }

    /**
     * @return array
     * 通过数据库查询热门话题
     */
    protected function getHotByDb()
    {
        $sql    = 'select count(1) num ,topic_content from f_posts_topics group by topic_content order by num desc limit 15, 20';
        $result = DB::select($sql);
        if (!empty($result)) {
            $result = array_map(function ($v){
                return ['topic_content' => $v->topic_content];
            }, $result);
        }
        return $result;
    }
}
