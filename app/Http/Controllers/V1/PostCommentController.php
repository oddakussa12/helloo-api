<?php

namespace App\Http\Controllers\V1;

use App\Resources\LikeCollection;
use App\Events\PostCommentCreated;
use App\Events\PostCommentDeleted;
use App\Services\TranslateService;
use App\Models\User;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Http\Request;
use App\Models\PostComment;
use App\Repositories\Contracts\PostCommentRepository;
use App\Resources\PostCommentCollection;
use Illuminate\Http\Response;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\StorePostCommentRequest;
use App\Jobs\PostCommentTranslation;

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
     * @return Response
     */
    public function store(StorePostCommentRequest $request)
    {
        $postUuid = $request->input('post_uuid');
        $commentContent = clean($request->input('comment_content' , ''));
        $commentPId = $request->input('comment_comment_p_id' , 0);
        $post = app(PostRepository::class)->findByUuid($postUuid);
        if(isset($commentPId)&&$commentPId!=0)
        {
            $this->postComment->find($commentPId);
	}
        $contentLang = $this->translate->detectLanguage($commentContent);
        $contentDefaultLang = $contentLang=='und'?'en':$contentLang;
        $comment = array(
            'post_id'=>$post->post_id,
            'user_id'=>auth()->id(),
            'comment_country_id'=>auth()->user()->user_country_id,
            'comment_comment_p_id'=>$commentPId,
            'comment_default_locale'=>$contentDefaultLang,
            'comment_verify'=>1,
            'comment_verified_at'=>date('Y-m-d H:i:s')
        );
//        foreach (supportedLocales() as $locale=>$properties)
//        {
//            if($contentLang!=$locale)
//            {
//                $comment[$locale] = array('comment_content'=>$this->translate->translate($commentContent , array('target'=>$locale)));
//            }
//        }
        dynamicSetLocales(array($contentDefaultLang));
        $translation = $this->translate->customizeTrans($commentContent , $contentLang);
//        $comment[$contentLang] = array('comment_content'=>$commentContent);
        $comment = $comment+$translation;
        $postComment = $this->postComment->store($comment);
        event(new PostCommentCreated($postComment));
        return new PostCommentCollection($postComment);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $one = $this->postComment->find($id);
        $edit = $this->edit($id);

        return $edit;
    }

    public function showByPostUuid(Request $request , $uuid)
    {
        return PostCommentCollection::collection($this->postComment->findByPostUuid($request , $uuid));
    }

    public function favorite($id)
    {
        $comment = $this->postComment->find($id);
        auth()->user()->favorite($comment);
        return $this->response->noContent();
    }

    public function unfavorite($id)
    {
        $comment = $this->postComment->find($id);
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
        $postComment = $this->postComment->find($id);
        auth()->user()->like($postComment);
        return $this->response->noContent();
    }

    public function dislike($id)
    {
        $postComment = $this->postComment->find($id);
        auth()->user()->unlike($postComment);
        return $this->response->noContent();
    }

    public function revokeVote($id)
    {
        $postComment = $this->postComment->find($id);
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
        //
        $postComment = $this->postComment->find($id);
        if($postComment->user_id!=auth()->id())
        {
            abort(401);
        }
        event(new PostCommentDeleted($postComment));
        $this->postComment->destroy($postComment);
        return $this->response->noContent();
    }

    public function myself(Request $request)
    {
        return PostCommentCollection::collection($this->postComment->findByUserId($request , auth()->user()->user_id));
    }


    public function mylike()
    {
        return LikeCollection::collection(auth()->user()->likes()->where('likable_type' , PostComment::class)->orderby('created_at' , 'desc')->with('likable')->paginate(5));
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
