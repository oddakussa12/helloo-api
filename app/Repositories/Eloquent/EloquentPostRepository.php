<?php

namespace App\Repositories\Eloquent;

use App\Models\PostTranslation;
use App\Models\VoteDetail;
use App\Models\VoteDetailTranslation;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Like;
use App\Jobs\TopicEs;
use App\Jobs\TopicPush;
use App\Models\Dislike;
use App\Custom\RedisList;
use App\Models\PostComment;
use App\Models\PostViewNum;
use Illuminate\Http\Request;
use App\Custom\Constant\Constant;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
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
        $include = $request->input('include', '');
        $include = explode(',', $include);
        $posts   = $this->allWithBuilder()->with('owner')->where('post_topping' , 1)
                        ->orderBy('post_topped_at', 'DESC')->limit(8)->get();

//        $activeUsers = app(UserRepository::class)->getYesterdayUserRank(); //获取活跃用户
//        $posts->each(function ($item, $key) use ($activeUsers){
//            $item->owner->user_medal = $activeUsers->where('user_id' , $item->user_id)->pluck('user_rank_score')->first();
//        });

        $posts = $this->authCheck($posts);
        if(in_array('follow' , $include)) {
            $userIds   = $posts->pluck('user_id')->all();//获取user id
            $followers = app(UserRepository::class)->userFollow($userIds);//重新获取当前登录用户信息
            $posts->each(function ($item, $key) use ($followers){
                $item->owner->user_follow_state = in_array($item->user_id , $followers);
            });
        }
        return $posts;
    }

    public function authCheck($posts)
    {
        if (auth()->check()) {
            $postIds      = $posts->pluck('post_id')->all();
            $postLikes    = $this->userPostLike($postIds);
            $postDisLikes = $this->userPostDislike($postIds);
            $posts->each(function ($post, $key) use ($postLikes, $postDisLikes) {
                $post->likeState    = in_array($post->post_id, $postLikes);
                $post->dislikeState = in_array($post->post_id, $postDisLikes);
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
        if(!empty($include)) {
            $appends['include'] = $include;
        }
        $include = explode(',', $include);
//        if($request->get('tag')!==null) {
//            $tag   = $request->get('tag');
//            $tag   = Tag::findFromString($tag);
//            $posts = $tag->posts();
//            $posts = $posts->with('translations');
//            $appends['tag'] = $tag;
//        } else {
//            $posts = $this->allWithBuilder();
//        }
        //$posts = $this->allWithBuilder();
        $posts = $this->model;
        $posts = $posts->withTrashed()->with('owner');

        if ($request->get('home')!== null) {
            $type      = $request->get('type', 'default');
            $order     = $request->get('order', 'desc')=='desc'?'desc':'asc';
            $orderBy   = $request->get('order_by', 'rate');
            $follow    = $request->get('follow');
            $queryTime = $request->get('query_time', '');

            $appends['home']     = $request->get('home');
            $appends['type']     = $type;
            $appends['order']    = $order;
            $appends['order_by'] = $orderBy;

            $posts = $posts->where('post_topping' , 0);

            if($type == 'default' && $orderBy =='rate' && $follow == null) {
                $posts = $this->getFinePosts($posts);

            } else if ($type=='essence' && $follow == null){
                $posts = $this->getCustomEssencePost($posts);

            } else if ($type == 'tmp' && $follow == null) {
                $posts = $this->getTmpPosts($posts);

            } else {
                if ($follow !== null && auth()->check()) {
                    $appends['follow'] = $follow;
//                    if($follow!== null&&auth()->check())
//                    {
                    $posts = $posts->select('posts.*')->join('common_follows', 'common_follows.followable_id', '=', 'posts.user_id')
                        ->where('common_follows.user_id', auth()->id())
                        ->where('common_follows.followable_type', "App\\Models\\User")
                        ->where('common_follows.relation', 'follow');
//                        $appends['follow'] = $request->get('follow');
//                        $userIds= auth()->user()->followings()->pluck('user_id')->toArray();
//                        $posts = $posts->whereIn('user_id',$userIds);
//                    }
                    $posts = $posts->whereNull($this->model->getDeletedAtColumn());
                    $posts->orderBy($this->model->getKeyName() , 'DESC');
                    if(empty($queryTime)) {
                        $queryTime = Carbon::now()->timestamp;
                    }
//                    $posts = $posts->where($this->model->getCreatedAtColumn() , '<=' , Carbon::createFromTimestamp($queryTime)->toDateTimeString());
                    $appends['query_time'] = $queryTime;
                    $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);
                }else{
                    $posts = $this->getNewPost($posts);
                }
            }
            if(in_array('follow' , $include)) {
                if($follow !== null&&auth()->check()) {
                    $posts->each(function ($item, $key){
                        $item->owner->user_follow_state = true;
                    });
                }else{
                    $userIds   = $posts->pluck('user_id')->all();//获取user id
                    $followers = app(UserRepository::class)->userFollow($userIds);//重新获取当前登录用户信息
                    $posts->each(function ($item, $key) use ($followers){
                        $item->owner->user_follow_state = in_array($item->user_id , $followers);
                    });
                }
            }
            // 投票贴
            $posts = $this->voteList($posts);

            if(auth()->check()) {

                if($follow === null) {
                    $user        = auth()->user();
                    $hiddenPosts = app(UserRepository::class)->hiddenPosts($user->user_id);
                    $hiddenUsers = app(UserRepository::class)->hiddenUsers($user->user_id);
                    $posts = $posts->setCollection($posts->getCollection()->filter(function($post) use ($hiddenPosts, $hiddenUsers){
                        return  !in_array($post->post_uuid, $hiddenPosts)&&!in_array($post->user_id, $hiddenUsers);
                    })->values());
                    if($posts->isEmpty())
                    {
                        $page_num = intval($request->query->get($this->pageName))+1;
                        $request->query->set($this->pageName , $page_num);
                        if ($page_num<3) {
                           return $this->paginateAll($request);
                        }
                    }
                }
                $postIds = $posts->pluck('post_id')->toArray();
                $locales = $posts->pluck('post_content_default_locale')->push('en')->push(locale())->unique()->values()->toArray();
                $postLikes    = $this->userPostLike($postIds);
                $postDisLikes = $this->userPostDislike($postIds);

                $posts->each(function ($post , $key) use ($postLikes , $postDisLikes) {
                    $post->likeState    = in_array($post->post_id , $postLikes);
                    $post->dislikeState = in_array($post->post_id , $postDisLikes);
                });
                $postTranslations = PostTranslation::whereIn('post_id' , $postIds)->whereIn('post_locale' , $locales)->get();
                $posts->each(function ($post , $key) use ($postTranslations) {
                    $post->translations = $postTranslations->where('post_id' , $post->post_id)->all();
                });
            }
            return $posts->appends($appends);

        } else {
            $posts = $posts->where('post_id' , 0);
        }
        $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);

        return $posts->appends($appends);
    }


    /**
     * @param $posts
     * @return mixed
     * 投票贴
     */
    public function voteList($posts)
    {
        $userId = auth()->check() ? auth()->user()->user_id : null;

        if ($posts instanceof Post) {
            $postIds = $posts->where('post_id', $posts->post_id)->where('post_type','vote')->pluck('post_id');
        } else {
            $postIds  = $posts->where('post_type','vote')->pluck('post_id');
        }

        // $voteList = VoteDetail::whereIn('post_id', $postIds)->with('voteDetailTranslate')->get();

        $voteList = VoteDetail::whereIn('post_id', $postIds)->get();
        $voteIds  = $voteList->pluck('id');
        $voteLang = $voteList->pluck('default_locale');

        $voteLang = array_unique(array_merge($voteLang->toArray(), [locale(), 'en']));


        $voteTrans= VoteDetailTranslation::whereIn('locale', $voteLang)->whereIn('vote_detail_id', $voteIds)->toSql();

//        dump($voteTrans);
//        dump($voteIds, $voteLang);
        $voteList->each(function ($vote) use ($userId, $voteTrans, $voteLang) {
            // $relation = $vote->getRelations()['voteDetailTranslate'];
            // $vote->content = $relation->content;
            $voteTrans->each(function ($trans, $index) use ($vote, $voteLang) {
                if ($vote->default_locale==$trans->locale) {
                    $vote->default_content = $trans->content;
                }
                if (!in_array($trans->locale, $voteLang)) {
                }
                if ($trans->locale==locale()) {
                    $vote->content = $trans->content;
                }
            });
            $count = $this->voteChoose($vote->post_id, $vote->id, $userId);
            foreach ($count as $key=>$item) {
                $vote->$key = $item;
            }
        });

        if ($posts instanceof Post) {
            $posts->voteInfo = $voteList;
        } else {
            $posts->each(function ($post) use ($voteList) {
                $tmp = $voteList->where('post_id', $post->post_id)->all();
                $post->voteInfo = collect(array_values($tmp));
            });
        }

        return $posts;
    }

    /**
     * @param $postId
     * @param $voteId
     * @param $userId
     * @return array 投票 是否选了某个选项
     *
     * 投票 是否选了某个选项
     */

    public function voteChoose($postId, $voteId, $userId)
    {
        $memKey          = config('redis-key.post.post_vote_data').$postId;
        $result          = Redis::hget($memKey, $voteId);
        $result          = !empty($result) ? json_decode($result, true) : ['users'=>[], 'country'=>[]];

        $data['choose']  = in_array($userId, $result['users']);
        $data['count']   = count($result['users']);

        $country = array_flip($result['country']);
        rsort($country);
        $data['country'] = array_slice($country, 0, 5);
        return $data;

    }

    public function paginateTopic($topic)
    {
        $request  = \request();
        $orderBy  = strval($request->input('order_by', 'time'));
        $key      = strval($topic);
        $topicKey = $orderBy==='time' ? $key.'_new' : $key.'_rate';
        $pageName = 'post_page';
        $perPage  = 8;
        $page     = intval($request->input($pageName, 1));
        $offset   = ($page-1)*$perPage;
        $redis    = new RedisList();
        $appends['order_by'] = $orderBy;

        if($redis->existsKey($topicKey)) {
            $total   = $redis->zSize($topicKey);
            $postIds = $redis->zRevRangeByScore($topicKey , '+inf' , '-inf' , true , array($offset , $perPage));
            $postIds = array_keys($postIds);
        } else {
            $total   = 0;
            $postIds = array();
        }
        $posts = $this->allWithBuilder();
        $posts = $posts->withTrashed()->with('owner');
        $posts = $posts->where('post_topping' , 0);
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $posts->whereIn('post_id' , $postIds)->orderBy($this->model->getCreatedAtColumn() , 'DESC')->get();
        $posts = $this->paginator($posts, $total, $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
        $postLikes    = $this->userPostLike($postIds);
        $postDisLikes = $this->userPostDislike($postIds);

        $posts->each(function ($post , $key) use ($postLikes , $postDisLikes) {
            $post->likeState    = in_array($post->post_id , $postLikes);
            $post->dislikeState = in_array($post->post_id , $postDisLikes);
        });
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

    public function paginateByUser(Request $request, $user)
    {
        $appends = array();
        $user    = !is_object($user) ? app(UserRepository::class)->findOrFail($user) : $user;
        $other   = !is_object($user);

        //新增帖子可见范围
        if ($other) {
            $posts = $user->posts()->where('show_type','<', 3)->with('translations');
        } else {
            $posts = $user->posts()->with('translations');
        }

        if ($request->get('order_by') !== null && $request->get('order') !== null) {
            $order   = $request->get('order') === 'asc' ? 'asc' : 'desc';
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

//        $userIds   = $posts->pluck('user_id')->all(); //获取分页user Id
//        $followers = app(UserRepository::class)->userFollow($userIds);//重新获取当前登录用户信息

        $postIds      = $posts->pluck('post_id');
        $postLikes    = $this->userPostLike($postIds);
        $postDisLikes = $this->userPostDislike($postIds);

        $posts->each(function ($item, $key) use ($postLikes , $postDisLikes , $user) {
            $item->owner        = $user;
            $item->likeState    = in_array($item->post_id , $postLikes);
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
        if (auth()->check()) {
            $authUser = auth()->id();
            $post = $this->findOrFailByUuid($uuid);
            if ($authUser != $post->user_id) {
                app(UserRepository::class)->updateHiddenPosts($authUser, $uuid);
            }
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
        $page = intval($request->input($pageName, 1));
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
        $now   = Carbon::now();
        $i     = intval($now->format('i'));
        $i     = $i <= 0 ? 1 : $i;
        $index = ceil($i/30);
        $indexSwitch = (bool)Redis::get('index_switch');
        if($indexSwitch)
        {
            $key   = config('redis-key.post.post_index_rate_v2').'_'.$index;
            if(!Redis::exists($key))
            {
                $key = 'post_index_rate_'.$index;
            }
        }else{
            $key   = 'post_index_rate_'.$index;
        }
        return $this->getCachePosts($posts, $key);
    }

    public function generatePostViewRandRank()
    {
        $postViewRankKey = 'post_view_rank';
        $postViewVirtualRankKey = 'post_view_virtual_rank';
        $redis = new RedisList();
        $redis->delKey($postViewRankKey);
        $redis->delKey($postViewVirtualRankKey);
        PostViewNum::chunk(20 , function($posts) use ($redis , $postViewRankKey , $postViewVirtualRankKey){
            foreach ($posts as $post) {
                $redis->zAdd($postViewRankKey , $post->post_view_num , $post->post_id);
                $redis->zAdd($postViewVirtualRankKey , post_view($post->post_view_num) , $post->post_id);
            }
        });
    }

    public function removeHideUser($post)
    {
        if(auth()->check()) {
            $hideUsers = app(UserRepository::class)->hiddenUsers();
            if(!empty($hideUsers)) {
                $post = $post->whereNotIn('user_id' , $hideUsers);
            }
        }
        return $post;
    }

    public function removeHidePost($post)
    {
        if(auth()->check()) {
            $hidePostUuid = app(UserRepository::class)->hiddenPosts();
            if(!empty($hidePostUuid)) {
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

    /**
     * @param $postId
     * @return array
     * 获取帖子国家
     */
    public function getPostCountry($postId)
    {
        $countryData = collect();
        $postKey = 'post.'.$postId.'.data';
        $field = 'country';
        if(Redis::exists($postKey) && Redis::hexists($postKey , $field)) {
            $countryData = collect(\json_decode(Redis::hget($postKey, $field) , true));
        }
        $countries = config('countries');
        $country   = $countryData->map(function($item , $key) use ($countries){
            return array('country_code'=>strtolower($countries[$key-1]), 'country_num'=>$item);
        })->sortByDesc('country_num')->values()->all();
        return $country;
    }

    /**
     * @param $posts
     * @return \Illuminate\Pagination\LengthAwarePaginator
     *
     * 获取自定义精华帖
     */
    public function getCustomEssencePost($posts)
    {
        $key = config('redis-key.post.post_index_essence');
        return $this->getCachePosts($posts, $key , 'post_id');
    }

    /**
     * @param $posts
     * @param $memKey
     * @param string $orderBy
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * 基础查询
     */
    public function getCachePosts($posts, $memKey, $orderBy='')
    {
        $request  = request();
        $perPage  = $this->perPage;
        $redis    = new RedisList();
        $pageName = $this->pageName;
        $page     = intval($request->input($pageName, 1));
        $order    = $request->get('order', 'desc') == 'desc' ? 'desc' : 'asc';

        $offset   = ($page-1) * $perPage;

        if ($redis->existsKey($memKey)) {
            $total = $redis->zSize($memKey);
            $postIds = $redis->zRevRangeByScore($memKey , '+inf' , '-inf' , true , array($offset , $perPage));
            $postIds = array_keys($postIds);
        } else {
            $total = 0;
            $postIds = array();
        }
        $posts = $posts->whereNull($this->model->getDeletedAtColumn());
        $posts = $posts->whereIn('post_id' , $postIds);

        $orderBy && $posts = $posts->orderBy($orderBy, $order);

        $posts = $posts->get();

        return $this->paginator($posts, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }


    public function getBlockList()
    {

    }

    /**
     * @param string $postId
     * @param bool $operation
     * @param int $score
     * 设置自定义精华帖
     */
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
        $oneMonthsAgo = $now->subDays(15)->format('Y-m-d 23:59:59');
        $threeMonthsAgo = $now->subDays(30)->format('Y-m-d 00:00:00');
        $posts->where('post_hotting' , 1)->where('post_created_at' , '>=' , $threeMonthsAgo)->where('post_created_at' , '<=' , $oneMonthsAgo)->where('post_comment_num' , '>' , 50)->orderBy('post_created_at' , 'DESC')->chunk(20 , function($posts) use ($redis , $postKey){
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
        $request  = request();
        $posts    = $this->allWithBuilder();
        $posts    = $posts->with('owner');
        $key      = 'post_index_fine';

        $appends['order']    = $request->get('order', 'desc') == 'desc' ? 'desc' : 'asc';
        $appends['order_by'] = 'post_comment_num';

        $posts = $this->getCachePosts($posts, $key, $appends['order_by']);
        $posts = $this->authCheck($posts);

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
        $posts->orderBy('post_comment_num' , 'DESC')->chunk(8 , function($posts) use ($redis , $postKey , &$i , $whereIn){
            if(!$whereIn) {
                $i++;
                if($i>10) {
                    return false;
                }
            }
            foreach ($posts as $post) {
                $redis->zAdd($postKey , $post->post_comment_num , $post->post_id);
            }
        });
    }

    public function attachTopics(Post $post, $topics)
    {
        $topics = array_filter($topics , function($v,$k){
            $v = str_replace(' ' , '' , $v);
            $v = ltrim($v , "#");
            return !empty($v);
        } , ARRAY_FILTER_USE_BOTH );
        $topics = array_map(function($v){
            $v = str_replace(' ' , '' , $v);
            $v = ltrim($v , "#");
            return mb_substr($v , 0 , 30);
        } , $topics);
        $topics = array_slice($topics,0 , 9);
        if(!blank($topics))
        {
            $topicPostCountKey = config('redis-key.topic.topic_post_count');
            $topicNewKey = config('redis-key.topic.topic_index_new');
            $now = time();
            $userId = $post->user_id;
            $postId = $post->getKey();
            $firstRate = first_rate_comment_v2();
            array_walk($topics , function($item , $index) use ($topicPostCountKey , $topicNewKey , $now , $userId , $postId , $firstRate){
                $key = strval($item);
                Redis::zincrby($topicPostCountKey , 1 , $key);
                //$pipe->zadd($topicNewKey , $now , $key);

                Redis::zadd($key."_new", $now , $postId);
                Redis::zadd($key."_rate", $firstRate , $postId);
                $userTopicKey = 'user.'.$userId.'.topics';
                Redis::zadd($userTopicKey, $now , $key);
            });
            $postKey = 'post.'.$postId.'.data';
            Redis::hmset($postKey, ["topics" => \json_encode($topics)]);

            // 组装数据 插入ES
            TopicEs::dispatch($post , $topics)->onQueue(Constant::QUEUE_ES_TOPIC);

            // 批量推送给关注者
            // TopicPush::dispatch($post, $topics)->onQueue(Constant::QUEUE_PUSH_TOPIC);

        }
        return $post;
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
        $rateV2KeyOne = config('redis-key.post.post_index_rate_v2').'_1';
        $rateV2KeyTwo = config('redis-key.post.post_index_rate_v2').'_2';
        $nonRateKey = config('redis-key.post.post_index_non_rate');
        $essencePostKey = config('redis-key.post.post_index_essence');
        $essenceManualPostKey = config('redis-key.post.post_index_essence_customize');
        if(!$op)
        {
            Redis::sadd($nonRateKey , $postId);
            Redis::zrem($rateKeyOne , $postId);
            Redis::zrem($rateKeyTwo , $postId);
            Redis::zrem($rateV2KeyOne , $postId);
            Redis::zrem($rateV2KeyTwo , $postId);
            Redis::zrem($essencePostKey , $postId);
            Redis::zrem($essenceManualPostKey , $postId);
        }else{
            Redis::srem($nonRateKey , $postId);
        }
    }

    public function userPostLike($postIds)
    {
        if(auth()->check()&&!empty($postIds))
        {
            return Like::where('user_id' , auth()->id())->WithType("App\Models\Post")->whereIn('common_likes.likable_id' , $postIds)->pluck('likable_id')->all();
        }
        return array();
    }

    public function userPostDislike($postIds)
    {
        if(auth()->check()&&!empty($postIds))
        {
            return Dislike::where('user_id' , auth()->id())->WithType("App\Models\Post")->whereIn('post_dislikes.dislikable_id' , $postIds)->pluck('dislikable_id')->all();
        }
        return array();
    }

    /**
     * @param $postId
     * @return mixed
     * 投票选项
     */
    public function voteInfo($postId)
    {
        //if(auth()->check()&&!empty($postId))
        if(!empty($postId)) {
            return VoteDetail::where('post_id', $postId)->get();
        }
    }
}
