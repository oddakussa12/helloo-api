<?php

/**
 * @Author: Dell
 * @Date:   2019-08-09 22:49:55
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-14 10:55:08
 */
namespace App\Repositories\Eloquent;

use App\Models\PostComment;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

class EloquentPostCommentRepository  extends EloquentBaseRepository implements PostCommentRepository
{
    public function findByPostUuid($request , $uuid)
    {
        $post = app(PostRepository::class)->findOrFailByUuid($uuid);
        $comments = $post->comments()
                        ->with('translations')
                        ->with('children')
                        ->where('comment_comment_p_id' , 0);
        if(auth()->check())
        {
            $comments = $comments->with(['likers'=>function($query){
                $query->where('users.user_id' , auth()->id());
            }]);
        }
        $comments = $comments->with('owner')
                        ->orderBy('comment_like_num', 'DESC')
                        ->paginate($this->perPage , ['*'] , $this->pageName);
        $comments->each(function ($item, $key) use ($uuid) {
            $item->post_uuid = $uuid;
        });
        if($request->get('children')==='true')
        {
            $comments->appends(array('children'=>'true'));
        }
        return $comments;
    }

    public function findByUserId($request , $user_id)
    {
        $comments = $this->allWithBuilder();
        $comments = $comments->where('user_id' , $user_id);
        $comments = $comments->with('likes')
            ->with('owner')
            ->with('post')
            ->whereHas('post');
        return $comments
            ->orderBy($this->model->getCreatedAtColumn(), 'desc')
            ->orderByDesc('comment_like_num')
            ->paginate($this->perPage , ['*'] , $this->pageName);
    }

    public function getCountByUserId($user_id)
    {
        return $this->model->where('user_id' , $user_id)->count();
    }

    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function find($id)
    {
        $comment = $this->allWithBuilder();
        $comment = $comment->with('owner')->with('post')->with('to')->with('likers')->find($id);
        return $comment;
    }

    public function myLike()
    {
        return auth()->user()
            ->likes()
            ->with(['likable'=>function($query){
                $query->with('owner')
                    ->with('translations')
                    ->with('likes')
                    ->with(['post'=>function($q){
                        $q->with('translations');
                    }]);
            }])
            ->join('posts_comments' , function($join){
                $join->on('common_likes.likable_id' , 'posts_comments.comment_id');
            })
            ->whereNull('posts_comments.comment_deleted_at')
            ->withType(PostComment::class)
            ->orderby('created_at' , 'desc')
            ->paginate(5);
    }

    public function findByCommentIds(array $comment_ids)
    {
        $comments = $this->allWithBuilder();
        return $comments->whereIn('comment_id' , $comment_ids)
            ->with('to')
            ->with('owner')
            ->with('likes')
            ->with(['post'=>function($q){
                $q->withTrashed();
            }])
            ->get();
    }
}
