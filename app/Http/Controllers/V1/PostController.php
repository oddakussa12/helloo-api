<?php

namespace App\Http\Controllers\V1;

use App\Models\Post;
use App\Jobs\PostTranslation;
use App\Services\TranslateService;
use App\Models\User;
use App\Resources\PostCollection;
use App\Resources\PostPaginateCollection;
use Illuminate\Http\Request;
use App\Events\PostViewEvent;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\UserRepository;
use Ramsey\Uuid\Uuid;

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
        $post = $this->post->findByUuid($uuid);
        auth()->user()->like($post);
        return $this->response->noContent();
    }


    public function favorite($uuid)
    {
        $post = $this->post->findByUuid($uuid);
        auth()->user()->favorite($post);
        return $this->response->noContent();
    }

    public function unfavorite($uuid)
    {
        $post = $this->post->findByUuid($uuid);
        auth()->user()->unfavorite($post);
        return $this->response->noContent();
    }


    public function dislike($uuid)
    {
        $user = User::find(auth()->id());
        $post = $this->post->findByUuid($uuid);
        $user->unlike($post);
        return $this->response->noContent();
    }

    public function revokeVote($uuid)
    {
        $post = $this->post->findByUuid($uuid);
        auth()->user()->revoke($post);
        return $this->response->noContent();
    }

    public function showPostByUser(Request $request , $userId)
    {
        return PostCollection::collection($this->post->paginateByUser($request , $userId));
    }

    public function showPostByUserSelf(Request $request)
    {
        return PostCollection::collection($this->post->paginateByUser($request , auth()->id()));
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
     * @param  \Illuminate\Http\Request  $request
     * @return PostCollection
     */
    public function store(Request $request)
    {
        $post_title = trim(strval($request->input('post_title')));
        $post_content = trim(strval($request->input('post_content' , '')));
        $post_category_id = 1;
        $post_type = 'text';
        $postTitleLang = $this->translate->detectLanguage($post_title);
        $post_title_default_locale = $postTitleLang=='und'?'en':$postTitleLang;
        if(empty($post_content))
        {
            $post_content_default_locale = $postContentLang = 'en';
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
            'post_topping' => 1,
            'post_topped_at' => date('Y-m-d H:i:s'),
        );
        dynamicSetLocales(array($post_title_default_locale , $post_content_default_locale));
        if($post_title_default_locale!=$post_content_default_locale)
        {
            $post_info[$post_title_default_locale] = array('post_title'=>$post_title,'post_content'=>'');
            $post_info[$post_content_default_locale] = array('post_title'=>'','post_content'=>$post_content);
        }else{
            $post_info[$post_title_default_locale] = array('post_title'=>$post_title,'post_content'=>$post_content);
        }
        $post = $this->post->store($post_info);
        //$this->dispatch(new PostTranslation($post , $post_title_default_locale , $post_content_default_locale , $post_title , $post_content));
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
//        $post = $this->post->findByUuid($uuid);
//        event(new PostViewEvent($post));
//        return $post;
    }

    public function showByUuid($uuid)
    {
        $post = $this->post->findByUuid($uuid);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function showTopList(Request $request)
    {
        return PostPaginateCollection::collection($this->post->top($request));
    }


    public function test()
    {
        dd(Post::withAnyTags(['news', 'knowledge'])->paginate(1)->toArray());die;
        $post = $this->post->find(896);
        $post->attachTags(array('news' , 'knowledge' , 'dd'));
    }

}
