<?php

/**
 * @Author: Dell
 * @Date:   2019-08-09 22:49:55
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-14 10:55:08
 */
namespace App\Repositories\Eloquent;

use App\Http\Requests\Request;
use App\Models\Post;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\PostCommentRepository;


class EloquentPostCommentRepository  extends EloquentBaseRepository implements PostCommentRepository
{
    public function topTwoComment($post_id)
    {
        return $this->model->where(['post_id'=>$post_id , 'comment_comment_p_id'=>0])->orderByDesc('comment_like_num')->limit(2)->get();
    }
    public function findByPostUuid($request , $uuid)
    {
        $post = app(PostRepository::class)->findByUuid($uuid);
        $comments = $post->comments();
        $comments->where('comment_comment_p_id', 0);
        if ($request->get('order_by') !== null && $request->get('order') !== null) {
            $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
            $comments->orderBy($request->get('order_by' , 'comment_like_num'), $order);
        }else{
            $comments->orderBy('comment_like_num', 'desc')->orderBy('comment_created_at', 'desc');
        }
        $comments = $comments->paginate($this->perPage , ['*'] , $this->pageName);
        if($request->get('children')==='true')
        {
            $comments->appends(array('children'=>'true'));
        }
        return $comments;
    }

    public function findByUserId($request , $user_id)
    {
        return $this->model
            ->where(['user_id'=>$user_id])
            ->orderBy('comment_created_at', 'desc')
            ->orderByDesc('comment_like_num')
            ->paginate($this->perPage , ['*'] , $this->pageName);
    }

    public function getCountByUserId($request , $user_id)
    {
        return $this->model
            ->where(['user_id'=>$user_id])
            ->count();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }


}
