<?php

namespace App\Http\Controllers\V1;

use App\Jobs\PostEs;
use App\Models\Post;
use Ramsey\Uuid\Uuid;
use App\Models\Banner;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use App\Events\PostViewEvent;
use App\Jobs\PostTranslation;
use App\Jobs\PostTranslationV2;
use App\Resources\PostCollection;
use App\Custom\Constant\Constant;
use App\Services\TranslateService;
use App\Resources\BannerCollection;
use Illuminate\Support\Facades\Redis;
use App\Http\Requests\StorePostRequest;
use App\Services\AzureTranslateService;
use App\Resources\PostPaginateCollection;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\UserRepository;

class PostController extends BaseController
{
    /**
     * @var PostRepository
     */
    private $post;
    /**
     * @var TranslateService||V3TranslateService
     */
    private $translate;

    /**
     * Display a listing of the resource.
     *
     * @param PostRepository $post
     * @param AzureTranslateService $azureTranslateService
     * @param TranslateService $translateService
     */

    public function __construct(PostRepository $post,AzureTranslateService $azureTranslateService , TranslateService $translateService)
    {
        $this->post = $post;
//        if(config('common.translation_version')==='niu')
//        {
//            $this->translate = $azureTranslateService;
//        }else{
//            $this->translate = $translateService;
//        }
        $this->translate = $translateService;
    }

    public function index(Request $request)
    {
        return PostPaginateCollection::collection($this->post->paginateAll($request));
    }

    public function like($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $response = $this->response->noContent();
        $user = auth()->user();
        if(app(UserRepository::class)->isProhibited($user))
        {
            return $response;
        }
        $country = auth()->user()->like($post);
        $num = 0;
        if($country!==false)
        {
            $num = $this->post->isNewCountry($post->getKey() , $country);
        }
        return $response->header('Country-Num', $num);
    }


    public function favorite($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        auth()->user()->favorite($post);
        return $this->response->noContent();
    }

    public function unfavorite($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        auth()->user()->unfavorite($post);
        return $this->response->noContent();
    }


    public function dislike($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $user = auth()->user();
        $country = $user->dislike($post);
        $response = $this->response->noContent();
        $num = 0;
        if($country!==false)
        {
            $num = $this->post->isNewCountry($post->getKey() , $country);
        }
        return $response->header('Country-Num', $num);
    }

    public function revokeLike($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $country = auth()->user()->revoke($post);
        $response = $this->response->noContent();
        $num = 0;
        if($country!==false)
        {
            $num = $this->post->isNewCountry($post->getKey() , $country);
        }
        return $response->header('Country-Num', $num);
    }

    public function revokeDislike($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $country = auth()->user()->revokeDislike($post);
        $response = $this->response->noContent();
        $num = 0;
        if($country!==false)
        {
            $num = $this->post->isNewCountry($post->getKey() , $country);
        }
        return $response->header('Country-Num', $num);
    }

    public function showPostByUser(Request $request , $userId)
    {
        return PostPaginateCollection::collection($this->post->paginateByUser($request , $userId));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return PostCollection
     * @throws \Exception
     */
    public function store(StorePostRequest $request)
    {
        $post_title         = clean($request->input('post_title' , ''));
        $post_content       = clean($request->input('post_content' , ''));
        $post_type          = $request->input('post_type');
        $post_event_country = $request->input('post_event_country');

	    $post_image         = $request->input('post_image' , []);
	    $post_image_size    = $request->input('post_image_size' , []);
	    $post_video         = $request->input('post_video' , []);
	    $longitude          = $request->input('longitude'); // 经度
	    $latitude           = $request->input('latitude'); // 纬度
	    $showType           = $request->input('show_type', 1); // 可见范围
        $post_category_id   = 1;
        $tag_slug           = array_diff((array)$request->input('tag_slug' , []),[null , '']);
        $topics             = array_diff((array)$request->input('topics' , []), [null , '']);

        \Validator::make(array('post_content'=>$post_content), [
            'post_content' => ['bail','required','string','between:1,3000'],
        ])->validate();

        $post_image = \array_filter($post_image , function($v , $k) use ($post_image_size){
            $flag   = !empty($v);
            if($flag===false) {
                unset($post_image_size[$k]);
            }
            return $flag;
        } , ARRAY_FILTER_USE_BOTH );
        ksort($post_image_size);
        ksort($post_image);


        $post_type = 'text';
        if(!empty($post_image)) {
            $post_category_id = 2;
            $post_type = 'image';
        }
        if(!empty($post_video)) {
            $post_category_id = 3;
            $post_type = 'video';
        }
        $poster   = auth()->user();
        if(app(UserRepository::class)->isProhibited($poster))
        {
            $uuid = config('common.prohibited_default_uuid');
            if(blank($uuid)) {
                return $this->response->created();
            }
            $post = $this->post->showByUuid($uuid);
            return new PostCollection($post);
        }
        try {
            $postTitleLang             = empty($post_title)    ? 'en' : $this->translate->detectLanguage($post_title);
            $post_title_default_locale = $postTitleLang=='und' ? 'en' : $postTitleLang;
            if(empty($post_content))
            {
                $postContentLang = 'und';
                $post_content_default_locale = 'en';
            }else{
                $postContentLang = $this->translate->detectLanguage($post_content);
                $post_content_default_locale = $postContentLang=='und'?'en':$postContentLang;
            }
        }catch (\Exception $e)
        {
            \Log::error(\json_encode($e->getMessage() , JSON_UNESCAPED_UNICODE));
            abort(424 , 'Sorry guys! We are updating our services in the next 24 hours. We apologize for the inconvenience !');
        }

        $titleLocale = niuAzureToGoogle($post_title_default_locale);
        $contentLocale = niuAzureToGoogle($post_content_default_locale);
        $post_info     = [
            'user_id'          => $poster->user_id,
            'post_uuid'        => Uuid::uuid1(),
            'post_category_id' => $post_category_id,
            'post_country_id'  => $poster->user_country_id,
            'post_type'        => $post_type,
            'longitude'        => $longitude,
            'latitude'         => $latitude,
            'show_type'        => $showType ?? 1,
            'post_rate'        => first_rate_comment_v2(),

            'post_default_locale'         => $titleLocale,
            'post_event_country_id'       => !empty($post_event_country) ? $post_event_country : '-1',
            'post_content_default_locale' => $contentLocale,
        ];

        if($post_category_id==2&&!empty($post_image))
        {
            $post_image = array_slice($post_image,0 , 9);
            $post_image_size = array_slice($post_image_size,0 , 9);
            $post_media_json = array('image'=>array(
                'image_from'=>'upload',
                'image_cover'=>$post_image[0],
                'image_url'=>$post_image,
                'image_size'=>$post_image_size,
                'image_count'=>count($post_image)
                ));
            $post_info['post_media'] = $post_media_json;
        }
        if($post_category_id==3&&!empty($post_video))
        {
            $video_url             = isset($post_video['video_url'])?$post_video['video_url']:'';
            $video_subtitle_locale = isset($post_video['video_subtitle_locale'])?$post_video['video_subtitle_locale']:'';
            $video_thumbnail_url   = isset($post_video['video_thumbnail_url'])?$post_video['video_thumbnail_url']:'';
            $video_time            = isset($post_video['video_time'])?$post_video['video_time']:0;
            $video_size            = isset($post_video['video_size'])?$post_video['video_size']:0;
            $video_subtitle_url    = isset($post_video['video_subtitle_url'])?$post_video['video_subtitle_url']:'';
            $post_media_json       = array('video'=>array(
                'video_from'=>'upload',
                'video_url'=>$video_url,
                'video_subtitle_locale'=>$video_subtitle_locale,
                'video_thumbnail_url'=>$video_thumbnail_url,
                'video_time'=>$video_time,
                'video_size'=>$video_size,
                'video_subtitle_url'=>$video_subtitle_url
            ));
            $post_info['post_media'] = $post_media_json;
        }
        dynamicSetLocales(array($titleLocale , $contentLocale));
        if($titleLocale!=$contentLocale)
        {
            !empty($post_title)&&$post_info[$titleLocale] = array('post_title'=>$post_title,'post_content'=>'');
            $post_info[$contentLocale] = array('post_title'=>'','post_content'=>$post_content);
        }else{
            $post_info[$titleLocale] = array('post_title'=>$post_title,'post_content'=>$post_content);
        }
        $post = $this->post->store($post_info);
	    if(!empty($tag_slug)) {
            //$post->attachTags($tag_slug);
        }
        if(!empty($topics)) {
            $post = $this->post->attachTopics($post , $topics);
        }

        if (config('common.translation_version')==='niu')
        {
            $job = new PostTranslationV2($poster , $post , $titleLocale , $contentLocale , $postTitleLang , $postContentLang , $post_title , $post_content);
        } else {
            $job = new PostTranslation($poster , $post , $titleLocale , $contentLocale , $postTitleLang , $postContentLang , $post_title , $post_content);
        }
//        $this->dispatchNow($job);
        $this->dispatch($job->onQueue(Constant::QUEUE_POST_TRANSLATION));

        return new PostCollection($post);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {

    }

    /**
     * @param $uuid
     * @return PostCollection
     * 新增了可见范围，需要判断
     */
    public function showByUuid($uuid)
    {
        $userId = auth()->check() ? auth()->user()->user_id : 0;
        $post   = $this->post->showByUuid($uuid);
        $post   = $this->post->voteList($post);

        if (!empty($userId)) {
            if ($post->user_id !=$userId) {
                // 登录状态 可见范围为自己可见
                if ($post->show_type==3 && $post->user_id != $userId) {
                    abort(404);
                }
                // 登录状态 可见范围为粉丝可见
                if ($post->show_type==2) {
                    $follow = $this->post->userFollowType($post->user_id, $userId);
                    empty($follow) && abort(404);
                }
            }
        } else {
            // 未登录状态 且可见范围为 粉丝、自己
            $post->show_type>1 && abort(404);
        }

        $postLikes = $this->post->userPostLike(array($post->post_id));
        $postDisLikes = $this->post->userPostDislike(array($post->post_id));
        $post->likeState = in_array($post->post_id , $postLikes);
        $post->dislikeState = in_array($post->post_id , $postDisLikes);
        event(new PostViewEvent($post));
        return new PostCollection($post);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    /*public function update(Request $request, $uuid)
    {
        $subtitle = $request->input('subtitle' , '');
        $language = $request->input('locale' , locale());
        $post = $this->post->findOrFailByUuid($uuid);
        if($post->post_type=='video')
        {
            $media = $post->post_media;
            $subtitles = (array)$media['video']['video_subtitle_url'];
            $subtitles = \array_filter($subtitles , function($v , $k){
                return !empty($v)&&!empty($k);
            } , ARRAY_FILTER_USE_BOTH );
            $subtitles[$language] = $subtitle;
            $media['video']['video_subtitle_url'] = $subtitles;
            $post->post_media = $media;
            $post->save();
        }
        return $this->response->accepted();
    }*/

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $user = auth()->user();
        if($post->user_id!=$user->user_id)
        {
            abort(403);
        }
        $this->post->destroy($post);
        $redis = new RedisList();
        if($post->post_created_at>config('common.score_date'))
        {
//            $user->decrement('user_score' , 2);
//            $userScoreRankKey = config('redis-key.user.score_rank');
//            $redis->zIncrBy($userScoreRankKey , -2 , $user->user_id);
        }
        $postId = $post->getKey();
        $userPostsKey = config('redis-key.user.posts');
        $redis->zIncrBy($userPostsKey , -1 , $user->user_id);
        $postKey = config('redis-key.post.post_index_new');
        $essencePostKey = config('redis-key.post.post_index_essence');
        $essenceManualPostKey = config('redis-key.post.post_index_essence_customize');

        $publicNewKey = config('redis-key.post.post_index_public_new');
        $publicNewOneKey = config('redis-key.post.post_index_public_new')."_1";
        $publicNewTwoKey = config('redis-key.post.post_index_public_new')."_2";
//        $rateKeyOne = config('redis-key.post.post_index_rate').'_1';
//        $rateKeyTwo = config('redis-key.post.post_index_rate').'_2';
        $redis->zRem($postKey , $postId);
//        $redis->zRem($rateKeyOne , $postId);
//        $redis->zRem($rateKeyTwo , $postId);
        $redis->zRem($essencePostKey , $postId);
        $redis->zRem($essenceManualPostKey , $postId);
        $redis->zRem($publicNewKey , $postId);
        Redis::srem($publicNewOneKey , $postId);
        Redis::srem($publicNewTwoKey , $postId);

        $topics = $post->getPostTopics($post->post_id);
        $topicPostCountKey = config('redis-key.topic.topic_post_count');
        !empty($topics)&&array_walk($topics , function($item , $index) use($topicPostCountKey , $post){
            $key = strval($item);
            Redis::zincrby($topicPostCountKey , -1 , $key);
            Redis::zrem($key."_new" , $post->post_id);
            Redis::zrem($key."_rate" , $post->post_id);
        });
        PostEs::dispatch($post , 'delete')->onQueue('post_es')->delay(now()->addSeconds(120));
        return $this->response->noContent();
    }

    public function banner()
    {
        $key = 'banner_index';
        if(!Redis::exists($key))
        {
            $banners = Banner::where('status' , 1)->orderByDesc('sort')->limit(20)->select('repeat' , 'sort' , 'type' , 'image' , 'value')->get();
            if(!$banners->isEmpty())
            {
                Redis::set($key , \json_encode($banners , JSON_UNESCAPED_UNICODE));
                Redis::expire($key , 86400);
            }
        }else{
            $banners = collect(\json_decode(Redis::get($key) , true));
        }
        $locale = locale();
        $banners = $banners->toArray();
        $ip = getRequestIpAddress();
        $country = geoip($ip)->iso_code;
        foreach ($banners as $index=>$banner)
        {
            if((isset($banner['repeat'])&&$banner['repeat']==1)||!isset($banner['image']))
            {
                unset($banners[$index]);
                continue;
            }
            $image = \json_decode($banner['image'] , true);
            if(isset($image[$locale]))
            {
                $banners[$index]['image'] = $image[$locale].'?imageMogr2/auto-orient/interlace/1|imageslim';
            }else{
                if(isset($image['en']))
                {
                    $banners[$index]['image'] = $image['en'].'?imageMogr2/auto-orient/interlace/1|imageslim';
                }else{
                    unset($banners[$index]);
                    continue;
                }
            }
            if(isset($banner['type'])&&$banner['type']=='h5')
            {
                $value = $banner['value'];
                $banners[$index]['value'] = $value."?country=".$country."&language=".$locale."&time=".time();
            }
        }
        $banners = collect($banners)->values()->sortByDesc('sort');
        return BannerCollection::collection($banners);
    }

    public function carousel()
    {
        $carousel = [];
        $key = 'banner_index';
        if(!Redis::exists($key))
        {
            return $this->response->array($carousel);
        }
        $banners = collect(\json_decode(Redis::get($key) , true));
        $locale = locale();
        $banners = $banners->toArray();
        $domain = config('common.qnUploadDomain.thumbnail_domain');
        foreach ($banners as $index=>$banner)
        {
            if($banner['type']!='postDetail'||!isset($banner['image']))
            {
                continue;
            }
            $image = \json_decode($banner['image'] , true);
            if(isset($image[$locale]))
            {
                $carousel[$banner['value']] = $domain.$image[$locale].'?imageMogr2/auto-orient/interlace/1|imageslim';
            }else{
                if(isset($image['en']))
                {
                    $carousel[$banner['value']] = $domain.$image['en'].'?imageMogr2/auto-orient/interlace/1|imageslim';
                }
            }
        }
        return $this->response->array($carousel);
    }

    public function top(Request $request)
    {
        return PostPaginateCollection::collection($this->post->top($request));
    }
    public function hot(Request $request)
    {
        return PostCollection::collection($this->post->hot($request));
    }

    public function myself(Request $request)
    {
        return PostPaginateCollection::collection($this->post->paginateByUser($request , auth()->user()));
    }

    public function fine()
    {
        return PostPaginateCollection::collection($this->post->getCustomFinePost());
    }


    public function block($uuid)
    {
        $this->post->blockPost($uuid);
        return $this->response->created();
    }

    public function country($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $postCountries = $this->post->getPostCountry($post->post_id);
        return $this->response->array($postCountries);
    }


    public function test()
    {
        dd(Post::withAnyTags(['news', 'knowledge'])->paginate(1)->to[]);die;
        $post = $this->post->find(896);
        $post->attachTags(array('news' , 'knowledge' , 'dd'));
    }

    public function autoStorePost()
    {
        $post = $this->post->autoStorePost();
        return $post;
    }
}
