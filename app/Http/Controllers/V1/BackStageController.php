<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Events\PostCommentDeleted;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

class BackStageController extends BaseController
{

    /**
     * @var PostCommentRepository
     */
    private $postComment;

    /**
     * @var UserRepository
     */
    private $user;

    /**
     * @var PostRepository
     */
    private $post;

    public function __construct(PostCommentRepository $postComment , UserRepository $user , PostRepository $post)
    {
        $this->postComment = $postComment;
        $this->user = $user;
        $this->post = $post;
    }

    public function index()
    {

    }

    public function destroyComment($id)
    {
        $postComment = $this->postComment->findOrFail($id);
        $user = $this->user->find($postComment->user_id);
        event(new PostCommentDeleted($user , $postComment));
        $this->postComment->destroy($postComment);
        return $this->response->noContent();
    }

    public function destroyPost($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $this->post->destroy($post);
        if($post->post_created_at>config('common.score_date'))
        {
            $user = $this->user->find($post->user_id);
            $user->decrement('user_score' , 2);
        }
        $redis = new RedisList();
        $postKey = 'post_index_new';
        $redis->zRem($postKey , $post->getKey());
        return $this->response->noContent();
    }

    public function getCustomEssencePost()
    {
        $posts = $this->post->allWithBuilder();
        $posts = $this->post->getCustomEssencePost($posts);
        return $this->response->item($posts);
    }


    public function setCustomEssencePost(Request $request , $postId)
    {
        $score = intval($request->input('score' , mt_rand(11111 , 99999)));
        $operation = (bool)$request->input('operation' , true);
        $this->post->setCustomEssencePost($postId , $operation , $score);
        return $this->response->noContent();
    }

    public function setCarousel(Request $request , $postUuid)
    {
        $locale = (string)$request->input('locale' , 'en');
        $image = (string)$request->input('image' , '');
        if(!empty($locale)&&!empty($image))
        {
            carousel_post($postUuid , $locale , $image);
        }
        return $this->response->noContent();
    }


}
