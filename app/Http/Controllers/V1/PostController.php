<?php

namespace App\Http\Controllers\V1;

use App\Models\User;
use App\Models\Post;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use App\Events\PostViewEvent;
use App\Jobs\PostTranslation;
use App\Resources\PostCollection;
use App\Services\TranslateService;
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
     * @var TranslateService
     */
    private $translate;

    /**
     * Display a listing of the resource.
     *
     * @param PostRepository $post
     */

    public function __construct(PostRepository $post,TranslateService $translate)
    {
        $this->post = $post;
        $this->translate = $translate;
    }

    public function index(Request $request)
    {
        return PostPaginateCollection::collection($this->post->paginateAll($request));
    }

    public function like($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        auth()->user()->like($post);
        return $this->response->noContent();
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
        $user = User::find(auth()->id());
        $post = $this->post->findOrFailByUuid($uuid);
        $user->unlike($post);
        return $this->response->noContent();
    }

    public function revokeVote($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        auth()->user()->revoke($post);
        return $this->response->noContent();
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
        \Validator::make(array('post_content'=>$post_content), [
            'post_content' => ['bail','required','string','between:1,3000'],
        ])->validate();
	    $tag_slug = array_diff($request->input('tag_slug' , array()),array(null , ''));
	    $post_image = $request->input('post_image' , array());
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


    public function test()
    {
        dd(Post::withAnyTags(['news', 'knowledge'])->paginate(1)->toArray());die;
        $post = $this->post->find(896);
        $post->attachTags(array('news' , 'knowledge' , 'dd'));
    }

}
