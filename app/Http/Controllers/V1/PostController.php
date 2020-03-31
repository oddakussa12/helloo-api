<?php

namespace App\Http\Controllers\V1;

use App\Models\Post;
use Ramsey\Uuid\Uuid;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use App\Events\PostViewEvent;
use App\Jobs\PostTranslation;
use App\Resources\PostCollection;
use App\Services\TranslateService;
use App\Services\V3TranslateService;
use App\Http\Requests\StorePostRequest;
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
     * @param TranslateService $translateService
     * @param V3TranslateService $v3TranslateService
     */

    public function __construct(PostRepository $post,TranslateService $translateService ,V3TranslateService $v3TranslateService)
    {
        $this->post = $post;
        if(config('common.google_translation_version')=='v2')
        {
            $this->translate = $translateService;
        }else{
            $this->translate = $v3TranslateService;
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
        \Log::error(\json_encode($request->all()));

        $post_title = clean($request->input('post_title' , ''));
        $post_content = clean($request->input('post_content' , ''));
        $post_event_country = $request->input('post_event_country');
        \Validator::make(array('post_content'=>$post_content), [
            'post_content' => ['bail','required','string','between:1,3000'],
        ])->validate();
	    $tag_slug = array_diff((array)$request->input('tag_slug' , array()),array(null , ''));
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
        $post_info= array(
            'user_id'=>auth()->id(),
            'post_uuid'=>Uuid::uuid1(),
            'post_category_id'=>$post_category_id,
            'post_country_id'=>auth()->user()->user_country_id,
            'post_default_locale'=>$post_title_default_locale,
            'post_content_default_locale'=>$post_content_default_locale,
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
            $post_media_json = \json_encode(array('image'=>array(
                'image_from'=>'upload',
                'image_cover'=>$post_image[0],
                'image_url'=>$post_image,
                'image_count'=>count($post_image)
                )));
            $post_info['post_media'] = $post_media_json;
        }
        if($post_category_id==3&&!empty($post_video))
        {
            $video_url = isset($post_video['video_url'])?$post_video['video_url']:'';
            $video_subtitle_locale = isset($post_video['video_subtitle_locale'])?$post_video['video_subtitle_locale']:'en';
            $video_thumbnail_url = isset($post_video['video_thumbnail_url'])?$post_video['video_thumbnail_url']:'';
            $video_time = isset($post_video['video_time'])?$post_video['video_time']:0;
            $video_size = isset($post_video['video_size'])?$post_video['video_size']:0;
            $video_subtitle_url = isset($post_video['video_subtitle_url'])?$post_video['video_subtitle_url']:'';
            $post_media_json = \json_encode(array('video'=>array(
                'video_from'=>'upload',
                'video_url'=>$video_url,
                'video_subtitle_locale'=>$video_subtitle_locale,
                'video_thumbnail_url'=>$video_thumbnail_url,
                'video_time'=>$video_time,
                'video_size'=>$video_size,
                'video_subtitle_url'=>$video_subtitle_url
            )));
            $post_info['post_media'] = $post_media_json;
        }
        dynamicSetLocales(array($post_title_default_locale , $post_content_default_locale));
        if($post_title_default_locale!=$post_content_default_locale)
        {
            $post_info[$post_title_default_locale] = array('post_title'=>$post_title,'post_content'=>'');
            $post_info[$post_content_default_locale] = array('post_title'=>'','post_content'=>$post_content);
        }else{
            $post_info[$post_title_default_locale] = array('post_title'=>$post_title,'post_content'=>$post_content);
        }
        $post = $this->post->store($post_info);
	    if(!empty($tag_slug))
        {
            $post->attachTags($tag_slug);
        }
	    $job = new PostTranslation($post , $post_title_default_locale , $post_content_default_locale , $postTitleLang , $postContentLang , $post_title , $post_content);
	    if(domain()!=domain(config('app.url')))
        {
            $this->dispatch($job->onQueue('test'));
        }else{
            $this->dispatch($job);
        }
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
        if($post->user_id!=auth()->id())
        {
            abort(401);
        }
        $this->post->destroy($post);
        if($post->post_created_at>config('common.score_date'))
        {
            $user = auth()->user();
            $user->decrement('user_score' , 2);
        }
        $redis = new RedisList();
        $postKey = 'post_index_new';
        $redis->zRem($postKey , $post->getKey());
        return $this->response->noContent();
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
