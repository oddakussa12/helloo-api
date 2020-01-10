<?php

namespace App\Repositories\Eloquent;

use Carbon\Carbon;
use App\Models\Tag;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Custom\RedisList;
use App\Models\PostComment;
use Illuminate\Http\Request;
use App\Jobs\PostTranslation;
use Illuminate\Support\Facades\DB;
use App\Services\TranslateService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
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
        $posts = $posts->with('owner')->with('likers')->with('dislikers');
        $posts = $posts->with('viewCount');
        $posts = $posts->where('post_topping' , 1);
        $posts = $posts->orderBy('post_topped_at', 'DESC')
            ->limit(8)
            ->get();

        $postIds = $posts->pluck('post_id')->all();//获取post Id

//        $topTwoComments = $this->topTwoComments($postIds);//评论前两条sql拼接

        $topCountries = $this->topCountries($postIds);//评论国家sql拼接

        $topCountryNum = $this->countryNum($postIds);//评论国家总数sql拼接

        $activeUsers = app(UserRepository::class)->getYesterdayUserRank(); //获取活跃用户

        $posts->each(function ($item, $key) use ($topCountries , $topCountryNum ,$activeUsers){
//            $item->topTwoComments = $topTwoComments->where('post_id',$item->post_id);
            $item->countries = $topCountries->where('post_id',$item->post_id)->values()->all();
            $item->countryNum = $topCountryNum->where('post_id',$item->post_id)->first();
            $item->owner->user_medal = $activeUsers->where('user_id' , $item->user_id)->pluck('user_rank_score')->first();
        });
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
        $appends['include'] = $include;
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
        $posts = $posts->with('likers')->with('dislikers')->withTrashed()->with('owner');
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
            }else{
                $posts = $posts->with('viewCount');
                $posts = $this->removeHidePost($posts);
                $posts = $this->removeHideUser($posts);
                if($follow!== null&&auth()->check())
                {
                    $appends['follow'] = $request->get('follow');
                    $userIds= auth()->user()->followings()->pluck('user_id')->toArray();
                    $posts = $posts->whereIn('user_id',$userIds);
                }
                if ($request->get('keywords') !== null) {
                    $keywords = $request->get('keywords');
                    $appends['keywords'] = $keywords;
                    $posts->whereHas('translations', function ($query) use ($keywords) {
                        $query->where('post_title', 'LIKE', "%{$keywords}%");
                    });
                }
                $posts = $posts->whereNull($this->model->getDeletedAtColumn());
                if(($orderBy=='rate'||$orderBy==null)&&$follow==null)
                {
                    $posts = $posts->where('post_fine' , 0);
                    $posts = $posts->where('post_hoting' , 1);
//                    $rate_coefficient = config('common.rate_coefficient');
//                    $posts->select(DB::raw("*,((`post_comment_num` + 1) / pow(floor((unix_timestamp(NOW()) - unix_timestamp(`post_created_at`)) / 3600) + 2,{$rate_coefficient})) AS `rate`"));
//                    $posts->orderBy('rate' , 'DESC');
                    $posts->orderBy('post_rate' , 'DESC');
                    $more_than_post_comment_num = config('common.more_than_post_comment_num');
                    $posts = $posts->where('post_comment_num' , '>' , $more_than_post_comment_num);
                }
                $posts->orderBy($this->model->getKeyName() , 'DESC');
                $queryTime = $request->get('query_time' , '');
                if(empty($queryTime))
                {
                    $queryTime = Carbon::now()->timestamp;
                }
                $posts = $posts->where($this->model->getCreatedAtColumn() , '<=' , Carbon::createFromTimestamp($queryTime)->toDateTimeString());
                $appends['query_time'] = $queryTime;

    //            $sorts = $this->getOrder($orderBy);
    //            foreach ($sorts as $sort)
    //            {
    //                $posts->orderBy($sort, $order);
    //            }
                $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);
            }

            $postIds = $posts->pluck('post_id')->all(); //获取分页post Id

//            $topTwoComments = $this->topTwoComments($postIds);//评论前两条sql拼接开

            $topCountries = $this->topCountries($postIds);//评论国家sql拼接

            $topCountryNum = $this->countryNum($postIds);//评论国家总数sql拼接


            $activeUsers = app(UserRepository::class)->getYesterdayUserRank();

            $posts->each(function ($item, $key) use ($topCountries , $topCountryNum , $activeUsers) {
//                $item->topTwoComments = $topTwoComments->where('post_id',$item->post_id);
                $item->countries = $topCountries->where('post_id',$item->post_id)->values()->all();
                $item->countryNum = $topCountryNum->where('post_id',$item->post_id)->first();
                $item->owner->user_medal = $activeUsers->where('user_id' , $item->user_id)->pluck('user_rank_score')->first();
            });

            if(in_array('follow' , $include))
            {
                $userIds = $posts->pluck('user_id')->all();//获取user id
                $followers = userFollow($userIds);//重新获取当前登录用户信息
                $posts->each(function ($item, $key) use ($followers){
                    $item->owner->user_follow_state = in_array($item->user_id , $followers);
                });
            }
            return $posts->appends($appends);
        }elseif ($request->get('keywords') !== null) {
            $keywords = $request->get('keywords');
            $appends['keywords'] = $keywords;
            $posts->whereHas('translations', function ($query) use ($keywords) {
                $query->where('post_title', 'LIKE', "%{$keywords}%");
            });
        }else{
            $posts = $posts->where('post_id' , 0);
        }
        $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);

        $postIds = $posts->pluck('post_id')->all(); //获取分页post Id

        $topCountries = $this->topCountries($postIds);//评论国家sql拼接

        $topCountryNum = $this->countryNum($postIds);//评论国家总数sql拼接

        $posts->each(function ($item, $key) use ($topCountries , $topCountryNum) {
            $item->countryNum = $topCountryNum->where('post_id',$item->post_id)->first();
            $item->countries = $topCountries->where('post_id',$item->post_id)->values()->all();
        });
        return $posts->appends($appends);
    }

    public function showByUuid($uuid)
    {
        $post = $this->model;
        $post = $post->where('post_uuid', $uuid);
//        $post = $post->with(['tags' => function($query){
//            $query->with('translations');
//        }]);
        $post = $post->firstOrFail();
//        $topCountries = $this->topCountries([$post->post_id]);
//        $topCountryNum = $this->countryNum([$post->post_id]);
//        $post->countries = $topCountries->where('post_id',$post->post_id)->values()->all();
//        $post->countryNum = $topCountryNum->where('post_id',$post->post_id)->first();
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
        $posts = $user->posts()->with('translations')->with('owner')->with('dislikers');
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

        $userIds = $posts->pluck('user_id')->all(); //获取分页user Id

        $postIds = $posts->pluck('post_id')->all(); //获取分页post Id

        $topCountries = $this->topCountries($postIds);//评论国家sql拼接

        $topCountryNum = $this->countryNum($postIds);//评论国家总数sql拼接

        $followers = userFollow($userIds);//重新获取当前登录用户信息

        $posts->each(function ($item, $key) use ($topCountries , $topCountryNum , $followers) {
            $item->countries = $topCountries->where('post_id',$item->post_id)->values()->all();
            $item->countryNum = $topCountryNum->where('post_id',$item->post_id)->first();
            $item->owner->user_follow_state = in_array($item->user_id , $followers);
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
        $post = $post->with('owner')->find($id);
        return $post;
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

//    public function getFinePosts($posts)
//    {
//        $appends =array();
//        $request = request();
//        $perPage = $this->perPage;
//        $redis = new RedisList();
//        $pageName = $this->pageName;
//        $page = $request->input( $pageName, 1);
//        $index = $request->input('index' , mt_rand(1 , 5));
//        $appends['index'] = $index;
//        $key = 'post_index_'.$index;
//        $offset = ($page-1)*$perPage;
//        if($redis->existsKey($key))
//        {
//            $total = $redis->zSize($key);
//            $postIds = $redis->zRangByScore($key , '-inf' , '+inf' , true , array($offset , $perPage));
//            $postIds = array_keys($postIds);
//        }else{
//            $total = 0;
//            $postIds = array();
//        }
//        $order = $request->get('order' , 'desc')=='desc'?'desc':'asc';
//        $appends['order'] = $order;
//        $orderBy = $request->get('order_by' , 'rate');
//        $appends['order_by'] = $orderBy;
//        $posts = $posts->with('viewCount');
//        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
//        $posts = $this->removeHidePost($posts);
//        $posts = $posts->whereIn('post_id' , $postIds)->inRandomOrder()->get();
//        $posts = $this->paginator($posts, $total, $perPage, $page, [
//            'path' => Paginator::resolveCurrentPath(),
//            'pageName' => $pageName,
//        ]);
//        return $posts->appends($appends);
//    }


    public function getFinePosts($posts)
    {
        $appends =array();
        $request = request();
//        $perPage = $this->perPage;
//        $redis = new RedisList();
//        $pageName = $this->pageName;
//        $page = $request->input( $pageName, 1);
//        $index = $request->input('index' , mt_rand(1 , 5));
//        $appends['index'] = $index;
//        $key = 'post_index_'.$index;
//        $offset = ($page-1)*$perPage;
//        if($redis->existsKey($key))
//        {
//            $total = $redis->zSize($key);
//            $postIds = $redis->zRangByScore($key , '-inf' , '+inf' , true , array($offset , $perPage));
//            $postIds = array_keys($postIds);
//        }else{
//            $total = 0;
//            $postIds = array();
//        }
        $order = $request->get('order' , 'desc')=='desc'?'desc':'asc';
        $appends['order'] = $order;
        $orderBy = $request->get('order_by' , 'post_created_at');
        $appends['order_by'] = $orderBy;
        $posts = $posts->with('viewCount');
        $posts = $posts->where('post_fine' , 1);
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $this->removeHidePost($posts);
        $posts = $this->removeHideUser($posts);
//        $rate_coefficient = config('common.rate_coefficient');
//        $posts->select(DB::raw("*,((`post_comment_num` + 1) / pow(floor((unix_timestamp(NOW()) - unix_timestamp(`post_created_at`)) / 3600) + 2,{$rate_coefficient})) AS `rate`"));
//        $posts->orderBy('rate' , 'DESC');
        $posts->orderBy('post_rate' , 'DESC');
        $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);
//        $posts = $this->paginator($posts, $total, $perPage, $page, [
//            'path' => Paginator::resolveCurrentPath(),
//            'pageName' => $pageName,
//        ]);
        return $posts->appends($appends);
    }

    public function getFinePostIds()
    {
        return Cache::rememberForever('fine_post', function() {
            return $this->model->where('post_topping' , 0)->where('post_fine' , 1)->select('post_id')->pluck('post_id')->all();
        });
    }

    public function generatePostIdRandRank()
    {

        $min = 1;
        $max = 10;
        $redis = new RedisList();
        $postIds = $this->getFinePostIds();
        $postCount = count($postIds);
        for ($i=$min ;$i<=$max;$i++)
        {
            $postRankKey = 'post_index_'.$i;
            $redis->delKey($postRankKey);
            foreach ($postIds as $postId)
            {
                $randScore = mt_rand(1 , $postCount*100);
                $redis->zAdd($postRankKey , $randScore , $postId);
            }

        }
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
        $posts = $posts->with('viewCount')->with('likers')->with('dislikers');
        $posts = $posts->with('owner');
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $posts->whereIn('post_id' , $postIds)->orderBy($orderBy , $order)->get();
        $posts = $this->paginator($posts, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
        $postIds = $posts->pluck('post_id')->all(); //获取分页post Id

        $topCountries = $this->topCountries($postIds);//评论国家sql拼接

        $topCountryNum = $this->countryNum($postIds);//评论国家总数sql拼接

        $activeUsers = app(UserRepository::class)->getYesterdayUserRank();

        $posts->each(function ($item, $key) use ($topCountries , $topCountryNum , $activeUsers) {
            $item->countries = $topCountries->where('post_id',$item->post_id)->values()->all();
            $item->countryNum = $topCountryNum->where('post_id',$item->post_id)->first();
            $item->owner->user_medal = $activeUsers->where('user_id' , $item->user_id)->pluck('user_rank_score')->first();
        });
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
    public function autoStorePost()
    {
        $postinfo = 'autopost/postlist/'.date('Ymd',time()).'.json';
        if(\Storage::exists($postinfo)) {
            $postinfo = \json_decode(\Storage::get($postinfo));
            if (!empty($postinfo)) {
                $todaytime = date('H',time());
                if(isset($postinfo->{$todaytime})){
                    $postinfo = $postinfo->{$todaytime};
                    if(!empty($postinfo->post_content)){
                        $userinfo = 'autopost/rankuser/'.$postinfo->post_country.'.json';
                        if(\Storage::exists($userinfo)) {
                            $userinfo = \json_decode(\Storage::get($userinfo));
                            if (!empty($userinfo)) {
                                $user_id = explode(',',$userinfo->user_id);
                                $key = array_rand($user_id,1);
                                $user_id =  $user_id[$key];
                                $user = User::find($user_id);
                                $post_title = clean($postinfo->post_title);
                                $post_content = clean($postinfo->post_content);
                                \Validator::make(array('post_content'=>$post_content), [
                                    'post_content' => ['bail','required','string','between:1,3000'],
                                ])->validate();
                                $tag_slug = '';
                                $post_image = explode(',',$postinfo->post_image);
                                $post_category_id = 1;
                                $post_type = 'text';
                                $post_image = \array_filter($post_image , function($v , $k){
                                    return !empty($v);
                                } , ARRAY_FILTER_USE_BOTH );
                                ksort($post_image);
                                if(!empty($post_image))
                                {
                                    $post_category_id = 2;
                                    $post_type = 'image';
                                }
                                $postTitleLang = empty($post_title)?'en':app(TranslateService::class)->detectLanguage($post_title);
                                $post_title_default_locale = $postTitleLang=='und'?'en':$postTitleLang;
                                if(empty($post_content))
                                {
                                    $postContentLang = 'und';
                                    $post_content_default_locale = 'en';
                                }else{
                                    $postContentLang = app(TranslateService::class)->detectLanguage($post_content);
                                    $post_content_default_locale = $postContentLang=='und'?'en':$postContentLang;
                                }
                                $post_info= array(
                                    'user_id'=>$user_id,
                                    'post_uuid'=>Uuid::uuid1(),
                                    'post_category_id'=>$post_category_id,
                                    'post_country_id'=>$user->user_country_id,
                                    'post_default_locale'=>$post_title_default_locale,
                                    'post_content_default_locale'=>$postinfo->post_content_default_locale?$postinfo->post_content_default_locale:$post_content_default_locale,
                                    'post_type' =>$post_type,
                                    'post_fine' => 1,
                                    "post_event_country_id" => $postinfo->post_event_country_id,
                                    'post_rate'=>first_rate_comment_v2()
                                );
                                if($post_category_id==2&&!empty($post_image))
                                {
                                    $post_image = array_slice($post_image,0 , 9);
                                    $post_media_json = \json_encode(array('image'=>array(
                                        'image_from'=>'upload',
                                        'image_cover'=>$post_image[0],
                                        'image_url'=>$post_image,
                                        'image_count'=>count($post_image)
                                    )));
                                    $post_info['post_media'] = $post_media_json;
                                }
                                $post = $this->store($post_info);
                                if(!empty($tag_slug))
                                {
                                    $post->attachTags($tag_slug);
                                }
                                PostTranslation::dispatch($post , $post_title_default_locale , $post_content_default_locale , $postTitleLang , $postContentLang , $post_title , $post_content);

                                return $post;
                            }
                        }
                    };
                }
            }
        }
    }
}
