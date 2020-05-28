<?php

namespace App\Repositories\Eloquent;

use Carbon\Carbon;
use App\Models\Tag;
use App\Custom\RedisList;
use App\Models\PostComment;
use App\Models\PostViewNum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Database\Concerns\BuildsQueries;


class EloquentPostRepository  extends EloquentBaseRepository implements PostRepository
{

    use BuildsQueries;

    public function all()
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
        }
        return $this->model->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
    }

    public function top($request)
    {
        $include = $request->input('include' , '');
        $include = explode(',' ,$include);
        $posts = $this->allWithBuilder();
        $posts = $posts->with('owner');
        $posts = $posts->where('post_topping' , 1);
        $posts = $posts->orderBy('post_topped_at', 'DESC')
            ->limit(8)
            ->get();
//        $activeUsers = app(UserRepository::class)->getYesterdayUserRank(); //获取活跃用户
//
//        $posts->each(function ($item, $key) use ($activeUsers){
//
//            $item->owner->user_medal = $activeUsers->where('user_id' , $item->user_id)->pluck('user_rank_score')->first();
//        });

        if(auth()->check())
        {
            $postIds = $posts->pluck('post_id')->all();
            $postLikes = userPostLike($postIds);
            $postDisLikes = userPostDislike($postIds);

            $posts->each(function ($post , $key) use ($postLikes , $postDisLikes) {
                $post->likeState = in_array($post->post_id , $postLikes);
                $post->dislikeState = in_array($post->post_id , $postDisLikes);
            });
        }
        if(in_array('follow' , $include))
        {
            $userIds = $posts->pluck('user_id')->all();//获取user id
            $followers = userFollow($userIds);//重新获取当前登录用户信息
            $posts->each(function ($item, $key) use ($followers){
                $item->owner->user_follow_state = in_array($item->user_id , $followers);
            });
        }
        return $posts;
    }

    public function hot($request)
    {
        $posts = $this->model;
        $posts = $posts->with('translations');
        return $posts->orderBy('post_rate', 'DESC')
            ->orderBy($this->model->getCreatedAtColumn(), 'DESC')
            ->limit(6)
            ->get();
    }

    public function paginate($perPage = 10, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:$pageName;
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->where('post_topping' , 0)->orderByDesc('post_like_num')->paginate($perPage , $columns , $pageName , $page);
        }
        return $this->model->orderByDesc('post_like_num')->paginate($perPage , $columns , $pageName , $page);
    }

    public function paginateAll(Request $request)
    {
        $appends = array();
        $include = $request->input('include' , '');
        if(!empty($include))
        {
            $appends['include'] = $include;
        }
        $include = explode(',' ,$include);
        if($request->get('tag')!==null)
        {
            $tag = $request->get('tag');
            $appends['tag'] = $tag;
            $tag = Tag::findFromString($tag);
            $posts = $tag->posts();
            $posts = $posts->with('translations');
        }else{
            $posts = $this->allWithBuilder();
        }
        $posts = $posts->withTrashed()->with('owner');
        if ($request->get('home')!== null){
            $appends['home'] = $request->get('home');
            $type = $request->get('type' , 'default');
            $appends['type'] = $type;
            $order = $request->get('order' , 'desc')=='desc'?'desc':'asc';
            $appends['order'] = $order;
            $orderBy = $request->get('order_by' , 'rate');
            $appends['order_by'] = $orderBy;
            $follow = $request->get('follow');
            $posts = $posts->where('post_topping' , 0);
            if($type=='default'&&$orderBy=='rate'&&$follow==null)
            {
                $posts = $this->getFinePosts($posts);
            }else if($type=='essence'&&$follow==null){
                $posts = $this->getCustomEssencePost($posts);
            }else if($type=='tmp'&&$follow==null){
                $posts = $this->getTmpPosts($posts);
            }else{
                if($follow!== null&&auth()->check())
                {
                    $posts = $posts->whereNull($this->model->getDeletedAtColumn());
                    if($follow!== null&&auth()->check())
                    {
                        $appends['follow'] = $request->get('follow');
                        $userIds= auth()->user()->followings()->pluck('user_id')->toArray();
                        $posts = $posts->whereIn('user_id',$userIds);
                    }
                    $posts->orderBy($this->model->getKeyName() , 'DESC');
                    $queryTime = $request->get('query_time' , '');
                    if(empty($queryTime))
                    {
                        $queryTime = Carbon::now()->timestamp;
                    }
//                    $posts = $posts->where($this->model->getCreatedAtColumn() , '<=' , Carbon::createFromTimestamp($queryTime)->toDateTimeString());
                    $appends['query_time'] = $queryTime;
                    $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);
                }else{
                    $posts = $this->getNewPost($posts);
                }
            }
            if(in_array('follow' , $include))
            {
                $userIds = $posts->pluck('user_id')->all();//获取user id
                $followers = userFollow($userIds);//重新获取当前登录用户信息
                $posts->each(function ($item, $key) use ($followers){
                    $item->owner->user_follow_state = in_array($item->user_id , $followers);
                });
            }

            if(auth()->check())
            {
                $user = auth()->user();
                $hiddenPosts = app(UserRepository::class)->hiddenPosts($user->user_id);
                $hiddenUsers = app(UserRepository::class)->hiddenUsers($user->user_id);
                $keys =  array();
                $postIds = array();
                $posts->each(function ($post , $key) use ($hiddenPosts , $hiddenUsers , &$keys , &$postIds) {
                    if(in_array($post->post_uuid , $hiddenPosts)||in_array($post->user_id , $hiddenUsers))
                    {
                        array_push($keys , $key);
                    }else{
                        array_push($postIds , $post->post_id);
                    }
                });
                $posts->offsetUnset($keys);
                $posts = $posts->setCollection($posts->values());

                $postLikes = userPostLike($postIds);
                $postDisLikes = userPostDislike($postIds);

                $posts->each(function ($post , $key) use ($postLikes , $postDisLikes) {
                    $post->likeState = in_array($post->post_id , $postLikes);
                    $post->dislikeState = in_array($post->post_id , $postDisLikes);
                });

            }
            return $posts->appends($appends);

        }else{
            $posts = $posts->where('post_id' , 0);
        }
        $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);

        return $posts->appends($appends);
    }

    public function showByUuid($uuid)
    {
        $post = $this->model;
        $post = $post->where('post_uuid', $uuid);
        $post = $post->firstOrFail();
        return $post;
    }

    public function findOrFailByUuid($uuid)
    {
        $post = $this->model;
        $post = $post->where('post_uuid', $uuid);
        return $post->firstOrFail();
    }

    public function paginateByUser(Request $request , $userId)
    {
        $appends = array();
        $user = app(UserRepository::class)->findOrFail($userId);
        $posts = $user->posts()->with('translations')->with('owner');
        if ($request->get('order_by') !== null && $request->get('order') !== null) {
            $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
            $orderBy = $request->get('order_by' , 'post_like_num');
            $appends['order'] = $order;
            $appends['order_by'] = $orderBy;
            $posts->orderBy($orderBy, $order);
        }else{
            $posts->orderBy('post_created_at' , 'desc');
        }
        if ($request->get('categoryId') !== null) {
            $categoryId = $request->get('categoryId');
            $appends['categoryId'] = $categoryId;
            $posts->where('post_category_id' , $categoryId);
        }
        $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);

//        $userIds = $posts->pluck('user_id')->all(); //获取分页user Id

//        $followers = userFollow($userIds);//重新获取当前登录用户信息

        $postIds = $posts->pluck('post_id');

        $postLikes = userPostLike($postIds);

        $postDisLikes = userPostDislike($postIds);

        $posts->each(function ($item, $key) use ($postLikes , $postDisLikes) {
//            $item->owner->user_follow_state = in_array($item->user_id , $followers);
            $item->likeState = in_array($item->post_id , $postLikes);
            $item->dislikeState = in_array($item->post_id , $postDisLikes);
        });

        return $posts->appends($appends);
    }

    public function getCountByUserId($user_id)
    {
        return $this->model->where('user_id',$user_id)->count();
    }
    public function getOrder($orderBy)
    {
        $sorts = array(
            'post_created_at' , 'post_rate' , 'post_like_num' , 'post_comment_num'
        );
        switch ($orderBy)
        {
            case 'time':
                $order_by = 'post_created_at';
                break;
            case 'like':
                $order_by = 'post_like_num';
                break;
            case 'comment':
                $order_by = 'post_comment_num';
                break;
            case 'rate':
            default:
                $order_by = 'post_rate';
                break;
        }
        $index = array_search($order_by , $sorts);
        unset($sorts[$index]);
        array_unshift($sorts , $order_by);
        return $sorts;
    }

    public function topTwoComments($postIds)
    {
        $topTwoCommentQuery = PostComment::whereIn('post_id',$postIds)
            ->select(DB::raw('*,@post := NULL ,@rank := 0'))
            ->orderBy('post_id')
            ->orderBy('comment_like_num' , 'DESC')
            ->orderBy('comment_created_at' , 'DESC');
        $topTwoCommentQuery = DB::table(DB::raw("({$topTwoCommentQuery->toSql()}) as b"))
            ->mergeBindings($topTwoCommentQuery->getQuery())
            ->select(DB::raw('b.*,IF (
                    @post = b.post_id ,@rank :=@rank + 1 ,@rank := 1
                ) AS rank,
                @post := b.post_id'));
        $topTwoCommentIds = DB::table( DB::raw("({$topTwoCommentQuery->toSql()}) as f_c") )
            ->mergeBindings($topTwoCommentQuery)
            ->where('rank','<',3)->select('c.comment_id')->pluck('comment_id')->toArray();

        $postComments = PostComment::whereIn('comment_id',$topTwoCommentIds)
            ->with('translations')
            ->with('owner');
        if(auth()->check())
        {
            $postComments = $postComments->with(['likers'=>function($query){
                $query->where('users.user_id' , auth()->id());
            }]);
        }
        return $postComments->get();
    }

    public function topCountries($postIds)
    {
        $topCountryQuery = PostComment::whereIn('post_id',$postIds)
            ->select(DB::raw('f_posts_comments.post_id,f_posts_comments.comment_country_id,f_countries.country_code,f_countries.country_name,count(*) AS country_num,@post := NULL ,@rank := 0'))
            ->leftJoin('countries', 'posts_comments.comment_country_id', '=', 'countries.country_id')
            ->groupBy('posts_comments.post_id')
            ->groupBy('posts_comments.comment_country_id')
            ->orderBy('post_id' , 'DESC')
            ->orderBy('country_num' , 'DESC')
            ->orderBy('comment_created_at' , 'DESC');
        $topCountryQuery = DB::table(DB::raw("({$topCountryQuery->toSql()}) as b"))
            ->mergeBindings($topCountryQuery->getQuery())
            ->select(DB::raw('b.*,IF (
                    @post = b.post_id ,@rank :=@rank + 1 ,@rank := 1
                ) AS rank,
                @post := b.post_id'));
        return DB::table(DB::raw("({$topCountryQuery->toSql()}) as f_c"))
            ->mergeBindings($topCountryQuery)
            ->where('rank','<',11)
            ->select('post_id', 'country_name' , 'country_code' , 'country_num')
            ->get();
    }

    public function countryNum($postIds)
    {
        $countryNumQuery = PostComment::whereIn('post_id',$postIds)
            ->select(DB::raw('*,@post := NULL'))
            ->orderBy('post_id')
            ->groupBy('post_id')
            ->groupBy('comment_country_id');

        $countryNumQuery = DB::table(DB::raw("({$countryNumQuery->toSql()}) as b"))
            ->mergeBindings($countryNumQuery->getQuery())
            ->select(DB::raw('b.*,IF (
                    @post = b.post_id ,@rank :=@rank + 1 ,@rank := 1
                ) AS rank,
                @post := b.post_id'));

        return  DB::table(DB::raw("({$countryNumQuery->toSql()}) as f_b"))
            ->mergeBindings($countryNumQuery)
            ->select('b.post_id' , DB::raw('count(f_b.comment_country_id) as country_num'))
            ->groupBy('b.post_id')->orderBy('b.post_id')->get();
    }

    public function find($id)
    {
        $post = $this->allWithBuilder();
        return $post->with('owner')->find($id);
    }

    public function findOrFailById($id)
    {
        $post = $this->model;
        $post = $post->where('post_id', $id);
        return $post->firstOrFail();
    }

    public function blockPost($uuid)
    {
        if(auth()->check())
        {
            app(UserRepository::class)->updateHiddenPosts(auth()->id() , $uuid);
        }
    }

    public function getTmpPosts($posts)
    {
        $appends =array();
        $request = request();
        $order = $request->get('order' , 'desc')=='desc'?'desc':'asc';
        $appends['order'] = $order;
        $orderBy = $request->get('order_by' , 'post_created_at');
        $appends['order_by'] = $orderBy;
        $posts = $posts->where('post_type' , 'tpm');
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts->orderBy($this->model->getCreatedAtColumn() , 'DESC');
        $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);
        return $posts->appends($appends);
    }

    public function getNewPost($posts)
    {
        $request = request();
        $perPage = $this->perPage;
        $redis = new RedisList();
        $pageName = $this->pageName;
        $page = $request->input( $pageName, 1);
        $key = config('redis-key.post.post_index_new');
        $offset = ($page-1)*$perPage;
        $queryTime = $request->get('query_time' , '');
        if(empty($queryTime))
        {
            $queryTime = Carbon::now()->timestamp;
        }
        $appends['query_time'] = $queryTime;
        if($redis->existsKey($key))
        {
            $total = $redis->zSize($key);
            $postIds = $redis->zRevRangeByScore($key , $queryTime , '-inf' , true , array($offset , $perPage));
            $postIds = array_keys($postIds);
        }else{
            $total = 0;
            $postIds = array();
        }
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $posts->whereIn('post_id' , $postIds)->orderBy($this->model->getCreatedAtColumn() , 'DESC')->get();
        $posts = $this->paginator($posts, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
        return $posts->appends($appends);
    }


    public function getFinePosts($posts)
    {
        $now = Carbon::now();
        $i = intval($now->format('i'));
        $i = $i<=0?1:$i;
        $index = ceil($i/30);
        $request = request();
        $perPage = $this->perPage;
        $redis = new RedisList();
        $pageName = $this->pageName;
        $page = $request->input( $pageName, 1);
        $key = 'post_index_rate_'.$index;
        $offset = ($page-1)*$perPage;
        if($redis->existsKey($key))
        {
            $total = $redis->zSize($key);
            $postIds = $redis->zRevRangeByScore($key , '+inf' , '-inf' , true , array($offset , $perPage));
            $postIds = array_keys($postIds);
        }else{
            $total = 0;
            $postIds = array();
        }
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $posts->whereIn('post_id' , $postIds)->get();
        return $this->paginator($posts, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    public function generatePostViewRandRank()
    {
        $postViewRankKey = 'post_view_rank';
        $postViewVirtualRankKey = 'post_view_virtual_rank';
        $redis = new RedisList();
        $redis->delKey($postViewRankKey);
        $redis->delKey($postViewVirtualRankKey);
        PostViewNum::chunk(20 , function($posts) use ($redis , $postViewRankKey , $postViewVirtualRankKey){
            foreach ($posts as $post)
            {
                $redis->zAdd($postViewRankKey , $post->post_view_num , $post->post_id);
                $redis->zAdd($postViewVirtualRankKey , post_view($post->post_view_num) , $post->post_id);
            }
        });
    }

    public function removeHideUser($post)
    {
        if(auth()->check())
        {
            $hideUsers = app(UserRepository::class)->hiddenUsers();
            if(!empty($hideUsers))
            {
                $post = $post->whereNotIn('user_id' , $hideUsers);
            }
        }
        return $post;
    }

    public function removeHidePost($post)
    {
        if(auth()->check())
        {
            $hidePostUuid = app(UserRepository::class)->hiddenPosts();
            if(!empty($hidePostUuid))
            {
                $post = $post->whereNotIn('post_uuid' , $hidePostUuid);
            }
        }
        return $post;
    }

    public function isNewCountry($id , $country)
    {
        $num = 0;
        $postKey = 'post.'.$id.'.data';
        $field = 'country';
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $countryData = \json_decode(Redis::hget($postKey, $field) , true);
            if(array_key_exists($country ,$countryData))
            {
                $num = $countryData[$country];
            }
        }
        return $num;
    }

    public function getPostCountry($postId)
    {
        $countryData = collect();
        $postKey = 'post.'.$postId.'.data';
        $field = 'country';
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $countryData = collect(\json_decode(Redis::hget($postKey, $field) , true));
        }
        $countries = config('countries');
        $country = $countryData->map(function($item , $key) use ($countries){
            return array('country_code'=>strtolower($countries[$key-1]) , 'country_num'=>$item);
        })->sortByDesc('country_num')->values()->all();
        return $country;
    }

    public function getCustomEssencePost($posts)
    {
        $request = request();
        $perPage = $this->perPage;
        $redis = new RedisList();
        $pageName = $this->pageName;
        $page = $request->input( $pageName, 1);
        $key = config('redis-key.post.post_index_essence');
        $offset = ($page-1)*$perPage;
        if($redis->existsKey($key))
        {
            $total = $redis->zSize($key);
            $postIds = $redis->zRevRangeByScore($key , '+inf' , '-inf' , true , array($offset , $perPage));
            $postIds = array_keys($postIds);
        }else{
            $total = 0;
            $postIds = array();
        }
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $posts->whereIn('post_id' , $postIds)->get();
        return $this->paginator($posts, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    public function setCustomEssencePost(string $postId , bool $operation=true , int $score=0)
    {
        $redis = new RedisList();
        $postKey = config('redis-key.post.post_index_essence');
        $key = config('redis-key.post.post_index_essence_customize');
        if(!empty($postId))
        {
            if($operation)
            {
                $score = $score==0?mt_rand(11111 , 99999):$score;
                $redis->zAdd($key , $score , $postId);
                $redis->zAdd($postKey , $score , $postId);
            }else{
                $redis->zRem($key , $postId);
                $redis->zRem($postKey , $postId);
            }
        }
    }

    public function customEssencePost()
    {
        $redis = new RedisList();
        $postKey = config('redis-key.post.post_index_essence');
        $redis->delKey($postKey);
        $posts = $this->model;
        $now = Carbon::now();
        $oneMonthsAgo = $now->subMonths(1)->format('Y-m-d 23:59:59');
        $threeMonthsAgo = $now->subMonths(2)->format('Y-m-d 00:00:00');
        $posts->where('post_created_at' , '>=' , $threeMonthsAgo)->where('post_created_at' , '<=' , $oneMonthsAgo)->where('post_comment_num' , '>' , 50)->orderBy('post_created_at' , 'DESC')->chunk(20 , function($posts) use ($redis , $postKey){
            foreach ($posts as $post)
            {
                $score = mt_rand(11111 , 99999);
                $redis->zAdd($postKey , $score , $post->post_id);
            }
        });
        $key = config('redis-key.post.post_index_essence_customize');
        if($redis->existsKey($key))
        {
            $total = $redis->zSize($key);
            $turn = 1;
            $perTurn = 10;
            $offset = ($turn-1)*$perTurn;
            while($offset<$total)
            {
                $postIds = $redis->zRevRangeByScore($key , '+inf' , '-inf' , true , array($offset , $perTurn));
                foreach ($postIds as $postId=>$score)
                {
                    $redis->zAdd($postKey , $score , $postId);
                }
                $turn++;
                $offset = ($turn-1)*$perTurn;
            }
        }
    }


    public function getCustomFinePost()
    {
        $appends =array();
        $posts = $this->allWithBuilder();
        $request = request();
        $perPage = $this->perPage;
        $redis = new RedisList();
        $pageName = $this->pageName;
        $page = $request->input( $pageName, 1);
        $key = 'post_index_fine';
        $offset = ($page-1)*$perPage;
        if($redis->existsKey($key))
        {
            $total = $redis->zSize($key);
            $postIds = $redis->zRevRangeByScore($key , '+inf' , '-inf' , true , array($offset , $perPage));
            $postIds = array_keys($postIds);
        }else{
            $total = 0;
            $postIds = array();
        }
        $order = $request->get('order' , 'desc')=='desc'?'desc':'asc';
        $appends['order'] = $order;
        $orderBy = 'post_comment_num';
        $appends['order_by'] = $orderBy;
        $posts = $posts->with('owner');
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $posts->whereIn('post_id' , $postIds)->orderBy($orderBy , $order)->get();
        $posts = $this->paginator($posts, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        if(auth()->check())
        {
            $postIds = $posts->pluck('post_id')->all();
            $postLikes = userPostLike($postIds);
            $postDisLikes = userPostDislike($postIds);
            $posts->each(function ($post , $key) use ($postLikes , $postDisLikes) {
                $post->likeState = in_array($post->post_id , $postLikes);
                $post->dislikeState = in_array($post->post_id , $postDisLikes);
            });
        }
        return $posts->appends($appends);
    }

    public function customFinePost()
    {
        $whereIn = false;
        $redis = new RedisList();
        $postKey = 'post_index_fine';
        $redis->delKey($postKey);
        $i = 0;
        $posts = $this->model;
        $finePostFile = 'tmp/finePost.json';
        if(\Storage::exists($finePostFile))
        {
            $whereIn = true;
            $finePost = \Storage::get($finePostFile);
            $finePost = \json_decode($finePost);
            $posts = $posts->whereIn('post_uuid' , $finePost);
        }
        $posts->orderBy('post_comment_num' , 'DESC')->chunk(8 , function($posts) use ($redis , $postKey , &$i , $whereIn){
                if(!$whereIn)
                {
                    $i++;
                    if($i>10)
                    {
                        return false;
                    }
                }
                foreach ($posts as $post)
                {
                    $redis->zAdd($postKey , $post->post_comment_num , $post->post_id);
                }
        });
    }


    public function autoIncreasePostView()
    {
        $redis = new RedisList();
        $postKey = 'post_view_virtual_rank';
        $perPage = 10;
        $count = $redis->zSize($postKey);
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++)
        {
            $offset = ($page-1)*$perPage;
            $posts = $redis->zRangByScore($postKey , '-inf' , '+inf' , true , array($offset , $perPage));
            foreach ($posts as $postId=>$score)
            {
                $add = mt_rand(50 , 150);
                $redis->zIncrBy($postKey , $add , $postId);
            }
        }
    }

    public function setNonFinePost($postId , $op=false)
    {
        $rateKeyOne = config('redis-key.post.post_index_rate').'_1';
        $rateKeyTwo = config('redis-key.post.post_index_rate').'_2';
        $nonRateKey = config('redis-key.post.post_index_non_rate');
        $essencePostKey = config('redis-key.post.post_index_essence');
        $essenceManualPostKey = config('redis-key.post.post_index_essence_customize');
        if(!$op)
        {
            Redis::sadd($nonRateKey , $postId);
            Redis::zrem($rateKeyOne , $postId);
            Redis::zrem($rateKeyTwo , $postId);
            Redis::zrem($essencePostKey , $postId);
            Redis::zrem($essenceManualPostKey , $postId);
        }else{
            Redis::srem($nonRateKey , $postId);
        }
    }
}
