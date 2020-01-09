<?php

namespace App\Http\Controllers\V1;

use App\CommentCollection;
use App\Resources\LikeCollection;
use App\Events\PostCommentCreated;
use App\Events\PostCommentDeleted;
use App\Services\TranslateService;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Http\Request;
use App\Models\PostComment;
use App\Repositories\Contracts\PostCommentRepository;
use App\Resources\PostCommentCollection;
use Illuminate\Http\Response;
use App\Jobs\PostCommentTranslation;
use App\Http\Requests\StorePostCommentRequest;

class PostCommentController extends BaseController
{
    /**
     * @var PostCommentRepository
     */
    private $postComment;
    /**
     * @var TranslateService
     */
    private $translate;

    /**
     * Display a listing of the resource.
     *
     * @param PostCommentRepository $postComment
     * @param TranslateService $translate
     */

    public function __construct(PostCommentRepository $postComment , TranslateService $translate)
    {
        $this->postComment = $postComment;
        $this->translate = $translate;
    }
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePostCommentRequest $request
     * @return PostCommentCollection
     */
    public function store(StorePostCommentRequest $request)
    {
        $postUuid = $request->input('post_uuid');
        $commentContent = clean($request->input('comment_content' , ''));
        $commentPId = $request->input('comment_comment_p_id' , 0);
        $post = app(PostRepository::class)->findOrFailByUuid($postUuid);
        $comment_image= $request->input('comment_image',array());
        $comment_image = \array_filter($comment_image , function($v , $k){
            return !empty($v);
        } , ARRAY_FILTER_USE_BOTH );
        if(empty($commentContent)&&empty($comment_image))
        {
            abort(422 , trans('validation.attributes.comment_content'));
        }
        if($commentPId!=0)
        {
            $comment_info = $this->postComment->findOrFail($commentPId);
            $comment_to_id =$comment_info->user_id;
            if($comment_info->comment_comment_p_id==0)
            {
                $comment_top_id =$commentPId;
            }else{
                $comment_top_id =$comment_info->comment_top_id;
            }
        }else{
            $comment_to_id =$post->user_id;
            $comment_top_id =0;
        }
        if(empty($commentContent))
        {
            $contentDefaultLang = $contentLang = 'en';
        }else{
            $contentLang = $this->translate->detectLanguage($commentContent);
            $contentDefaultLang = $contentLang=='und'?'en':$contentLang;
        }
        $user = auth()->user();
        $comment = array(
            'post_id'=>$post->post_id,
            'user_id'=>$user->user_id,
            'comment_country_id'=>$user->user_country_id,
            'comment_comment_p_id'=>$commentPId,
            'comment_top_id'=>$comment_top_id,
            'comment_default_locale'=>$contentDefaultLang,
            'comment_verify'=>1,
            'comment_verified_at'=>date('Y-m-d H:i:s'),
            'comment_to_id'=>$comment_to_id,
            'comment_image'=>\json_encode($comment_image),
        );

        if(empty($commentContent))
        {
            $postComment = $this->postComment->store($comment);
            event(new PostCommentCreated($post , $postComment , $user));
        }else{
            dynamicSetLocales(array($contentDefaultLang));

            $comment[$contentDefaultLang] = array('comment_content'=>$commentContent);

            $postComment = $this->postComment->store($comment);

            event(new PostCommentCreated($post , $postComment , $user));

            $job = new PostCommentTranslation($postComment , $contentLang , $commentContent);

            if(domain()!=domain(config('app.url')))
            {
                $this->dispatch($job->onQueue('test'));
            }else{
                $this->dispatch($job);
            }
        }
        $postComment->post_uuid = $postUuid;
        return new PostCommentCollection($postComment);
    }


    public function moreComment(Request $request , $commentTopId)
    {
        $postUuid = $request->input('post_uuid' , '');
        $commentLastId = $request->input('comment_last_id' , 0);
        $queryTime = $request->get('query_time' , '');
        $queryTime = empty($queryTime)?$queryTime:date('Y-m-d H:i:s' , strtotime($queryTime));
        $comments = $this->postComment->findByCommentTopId($postUuid , $commentTopId , $commentLastId , $queryTime);
        return PostCommentCollection::collection($comments);
    }

    public function locateComment(Request $request , $commentId)
    {
        $postUuid = $request->input('post_uuid' , '');
        $commentLastId = $request->input('comment_last_id' , 0);
        $queryTime = $request->get('query_time' , '');
        $queryTime = empty($queryTime)?$queryTime:date('Y-m-d H:i:s' , strtotime($queryTime));
        $comments = $this->postComment->findByLocateCommentId($request , $commentId);
        return $comments;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $one = $this->postComment->findOrFail($id);
        $edit = $this->edit($id);

        return $edit;
    }

    public function showByPostUuid(Request $request , $uuid)
    {
        return PostCommentCollection::collection($this->postComment->findByPostUuid($request , $uuid));
    }

    public function favorite($id)
    {
        $comment = $this->postComment->findOrFail($id);
        auth()->user()->favorite($comment);
        return $this->response->noContent();
    }

    public function unfavorite($id)
    {
        $comment = $this->postComment->findOrFail($id);
        auth()->user()->unfavorite($comment);
        return $this->response->noContent();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return 'edit';
    }

    public function like($id)
    {
        $postComment = $this->postComment->findOrFail($id);
        auth()->user()->like($postComment);
        return $this->response->noContent();
    }

    public function dislike($id)
    {
        $postComment = $this->postComment->findOrFail($id);
        auth()->user()->unlike($postComment);
        return $this->response->noContent();
    }

    public function revokeVote($id)
    {
        $postComment = $this->postComment->findOrFail($id);
        auth()->user()->revoke($postComment);
        return $this->response->noContent();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, PostComment $postcomment)
    {
        $result = $request->all();
        return $this->postcomment->update($postcomment,$result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $postComment = $this->postComment->findOrFail($id);
        $user = auth()->user();
        if($postComment->user_id!=$user->user_id)
        {
            abort(401);
        }
        event(new PostCommentDeleted($user , $postComment));
        $this->postComment->destroy($postComment);
        return $this->response->noContent();
    }

    public function myself(Request $request)
    {
        return PostCommentCollection::collection($this->postComment->findByUserId($request , auth()->user()->user_id));
    }


    public function mylike()
    {
        return LikeCollection::collection($this->postComment->myLike());
    }

    public function showPostCommentByUser(Request $request , $userId)

    {
        return PostCommentCollection::collection($this->postComment->findByUserId($request , $userId));
    }

    public function showPostCommentLikeByUser(Request $request , $userId)
    {
        $user = app(UserRepository::class)->findOrFail($userId);
        return LikeCollection::collection($user->likes()->where('likable_type' , PostComment::class)->orderby('created_at' , 'desc')->with('likable')->paginate(5));
    }
}
