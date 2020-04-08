<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Events\PostCommentDeleted;
use App\Repositories\Contracts\UserRepository;
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

    public function __construct(PostCommentRepository $postComment , UserRepository $user)
    {
        $this->postComment = $postComment;
        $this->user = $user;
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


}
