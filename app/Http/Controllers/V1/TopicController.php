<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Models\Topic;
use App\Models\HotTopic;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use App\Resources\PostPaginateCollection;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Database\Concerns\BuildsQueries;
use App\Resources\TopicSearchPaginateCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $result = $this->getHot();
        $hotDb  = $this->getHotByDb();
        $result = array_merge($result, $hotDb);
        return TopicSearchPaginateCollection::collection(collect($result));
    }

    protected function getHot()
    {
        $now = Carbon::now();
        $nowTime = $now->timestamp;
        $key = "hot_topic_customize";
        if(!Redis::exists($key))
        {
            $topics = HotTopic::where('start_time' , '<=' , $nowTime)
                ->where('end_time' , '>=' , $nowTime)->select('flag','sort', 'topic_content')->orderBy('flag')->orderBy('sort' , "DESC")->limit(10)->get()->toArray();
            Redis::set($key , \json_encode($topics));
            Redis::expire($key , 86400);
        }else{
            $topics = \json_decode(Redis::get($key) , true);
        }
        return $topics;
    }

    /**
     * @return array
     * 通过数据库查询热门话题
     */
    protected function getHotByDb()
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $startTime = $today->subDays(100)->timestamp;
        $endTime = $now->timestamp;
        $key = "hot_topic_auto";
        if(!Redis::exists($key))
        {
            $topics = Topic::where('topic_created_at' , '<=' , $endTime)
                ->where('topic_created_at' , '>=' , $startTime)->select('topic_content', DB::raw('COUNT(id) as num'))->groupBy('topic_content')->orderBy('num' , "DESC")->limit(10)->get()->map(function($item , $index){
                    return array('topic_content'=>$item->topic_content);
                })->toArray();
            Redis::set($key , \json_encode($topics));
            Redis::expire($key , 86400);
        }else{
            $topics = \json_decode(Redis::get($key) , true);
        }
        return $topics;
    }
}
