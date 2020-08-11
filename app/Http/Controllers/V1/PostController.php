<?php

namespace App\Http\Controllers\V1;

use App\Models\Post;
use Ramsey\Uuid\Uuid;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use App\Events\PostViewEvent;
use App\Jobs\PostTranslation;
use App\Jobs\PostTranslationV2;
use App\Resources\PostCollection;
use App\Services\TranslateService;
use App\Http\Requests\StorePostRequest;
use App\Services\AzureTranslateService;
use App\Resources\PostPaginateCollection;
use App\Repositories\Contracts\PostRepository;

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
        if(config('common.translation_version')==='niu')
        {
            $this->translate = $azureTranslateService;
        }else{
            $this->translate = $translateService;
        }
    }

    public function index(Request $request)
    {
        return PostPaginateCollection::collection($this->post->paginateAll($request));
    }

    public function like($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $country = auth()->user()->like($post);
        $response = $this->response->noContent();
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

    public function showPostByUserSelf(Request $request)
    {
        return PostPaginateCollection::collection($this->post->paginateByUser($request , auth()->id()));
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
        $post_title = clean($request->input('post_title' , ''));
        $post_content = clean($request->input('post_content' , ''));
        $post_event_country = $request->input('post_event_country');
        \Validator::make(array('post_content'=>$post_content), [
            'post_content' => ['bail','required','string','between:1,3000'],
        ])->validate();
	    $tag_slug = array_diff((array)$request->input('tag_slug' , array()),array(null , ''));
	    $topics = array_diff((array)$request->input('topics' , array()),array(null , ''));
	    $post_image = $request->input('post_image' , array());
	    $post_video = $request->input('post_video' , array());
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
        if(!empty($post_video))
        {
            $post_category_id = 3;
            $post_type = 'video';
        }
        $prohibited_content = config('common.prohibited_content');
        if(!blank($prohibited_content)&&str_contains($post_content , $prohibited_content))
        {
            $uuid = config('common.prohibited_default_uuid');
            if(blank($uuid))
            {
                return $this->response->created();
            }
            $post = $this->post->showByUuid($uuid);
            return new PostCollection($post);
        }
        try {
            $postTitleLang = empty($post_title)?'en':$this->translate->detectLanguage($post_title);
            $post_title_default_locale = $postTitleLang=='und'?'en':$postTitleLang;
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
        $poster = auth()->user();
        $titleLocale = niuAzureToGoogle($post_title_default_locale);
        $contentLocale = niuAzureToGoogle($post_content_default_locale);
        $post_info= array(
            'user_id'=>$poster->user_id,
            'post_uuid'=>Uuid::uuid1(),
            'post_category_id'=>$post_category_id,
            'post_country_id'=>$poster->user_country_id,
            'post_default_locale'=>$titleLocale,
            'post_content_default_locale'=>$contentLocale,
            'post_type' =>$post_type,
            'post_rate'=>first_rate_comment_v2()
        );
        if(!empty($post_event_country))
        {
            $post_info['post_event_country_id'] = $post_event_country;
        }
        if($post_category_id==2&&!empty($post_image))
        {
            $post_image = array_slice($post_image,0 , 9);
            $post_media_json = array('image'=>array(
                'image_from'=>'upload',
                'image_cover'=>$post_image[0],
                'image_url'=>$post_image,
                'image_count'=>count($post_image)
                ));
            $post_info['post_media'] = $post_media_json;
        }
        if($post_category_id==3&&!empty($post_video))
        {
            $video_url = isset($post_video['video_url'])?$post_video['video_url']:'';
            $video_subtitle_locale = isset($post_video['video_subtitle_locale'])?$post_video['video_subtitle_locale']:'';
            $video_thumbnail_url = isset($post_video['video_thumbnail_url'])?$post_video['video_thumbnail_url']:'';
            $video_time = isset($post_video['video_time'])?$post_video['video_time']:0;
            $video_size = isset($post_video['video_size'])?$post_video['video_size']:0;
            $video_subtitle_url = isset($post_video['video_subtitle_url'])?$post_video['video_subtitle_url']:'';
            $post_media_json = array('video'=>array(
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
            $post_info[$titleLocale] = array('post_title'=>$post_title,'post_content'=>'');
            $post_info[$contentLocale] = array('post_title'=>'','post_content'=>$post_content);
        }else{
            $post_info[$titleLocale] = array('post_title'=>$post_title,'post_content'=>$post_content);
        }
        $post = $this->post->store($post_info);
	    if(!empty($tag_slug))
        {
            $post->attachTags($tag_slug);
        }
        if(!empty($topics))
        {
            $post = $this->post->attachTopics($post , $topics);
        }
        if(config('common.translation_version')==='niu')
        {
            $job = new PostTranslationV2($poster , $post , $titleLocale , $contentLocale , $postTitleLang , $postContentLang , $post_title , $post_content);
        }else{
            $job = new PostTranslation($poster , $post , $titleLocale , $contentLocale , $postTitleLang , $postContentLang , $post_title , $post_content);
        }
        $this->dispatch($job);
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

    public function showByUuid($uuid)
    {
        $post = $this->post->showByUuid($uuid);
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
    public function update(Request $request, $uuid)
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
    }

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
            $user->decrement('user_score' , 2);
            $userScoreRankKey = config('redis-key.user.score_rank');
            $redis->zIncrBy($userScoreRankKey , -2 , $user->user_id);
        }
        $userPostsKey = config('redis-key.user.posts');
        $redis->zIncrBy($userPostsKey , -1 , $user->user_id);
        $postKey = config('redis-key.post.post_index_new');
        $redis->zRem($postKey , $post->getKey());
        return $this->response->noContent();
    }

    public function carousel()
    {
        return $this->response->array(
            carousel_post_list()
        );
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
        return PostPaginateCollection::collection($this->post->paginateByUser($request , auth()->user()->user_id));
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
        dd(Post::withAnyTags(['news', 'knowledge'])->paginate(1)->toArray());die;
        $post = $this->post->find(896);
        $post->attachTags(array('news' , 'knowledge' , 'dd'));
    }

    public function autoStorePost()
    {
        $post = $this->post->autoStorePost();
        return $post;
    }
}
