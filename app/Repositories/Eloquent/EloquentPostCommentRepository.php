<?php

/**
 * @Author: Dell
 * @Date:   2019-08-09 22:49:55
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-14 10:55:08
 */
namespace App\Repositories\Eloquent;

use Carbon\Carbon;
use App\Models\PostComment;
use Illuminate\Support\Facades\DB;
use App\Resources\PostCommentCollection;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

class EloquentPostCommentRepository  extends EloquentBaseRepository implements PostCommentRepository
{
    public function findByPostUuid($request , $uuid)
    {
        $appends = array();
        $queryTime = $request->get('query_time' , '');
        $post = app(PostRepository::class)->findOrFailByUuid($uuid);
        $comments = $post->comments()->withTrashed()
            ->with('translations')
            ->where('comment_comment_p_id' , 0)
            ->whereNull($this->model->getDeletedAtColumn());
        if(empty($queryTime))
        {
            $queryTime = Carbon::now()->timestamp;
        }
        $dateTime = Carbon::createFromTimestamp($queryTime)->toDateTimeString();
        $comments = $comments->where('comment_created_at' , '<=' , $dateTime);
        $appends['query_time'] = $queryTime;
        if(auth()->check())
        {
            $comments = $comments->with(['likers'=>function($query){
                $query->where('users.user_id' , auth()->id());
            }]);
        }
        $comments = $comments->with('owner')
            ->orderBy('comment_like_num', 'DESC')
            ->paginate($this->perPage , ['*'] , $this->pageName);
        $commentIds = $comments->pluck('comment_id')->all();//获取 comment Id

        $topTwoComments = $this->topTwoComments($commentIds , $dateTime);

        $userIds = $topTwoComments->pluck('user_id')->merge($topTwoComments->pluck('comment_to_id'))->unique();

        $users = app(UserRepository::class)->findByMany($userIds->all());

        $subCommentsCount = $this->getChildCountByCommentIds($commentIds , $dateTime);

        $topTwoComments->each(function($item , $key) use ($uuid , $users){
            $item->post_uuid = $uuid;
            $item->owner = $users->where('user_id' , $item->user_id)->first();
            $item->toer = $users->where('user_id' , $item->comment_to_id)->first();
        });

        $comments->each(function ($item, $key) use ($uuid , $topTwoComments , $subCommentsCount) {
            $item->post_uuid = $uuid;
            $item->topTwoComments = $topTwoComments->where('comment_top_id',$item->comment_id);
            $item->subCommentsCount = collect($subCommentsCount->where('comment_top_id',$item->comment_id)->first())->get('num' , 0);
        });
        $comments->appends(array('query_time'=>$queryTime));
        if($request->get('children')==='true')
        {
            $appends['children'] = true;
        }
        $comments->appends($appends);
        return $comments;
    }

    public function findByCommentTopId($postUuid , $commentTopId , $commentLastId , $queryTime=null)
    {
        $comments = $this->allWithBuilder();
        if($commentLastId!=0)
        {
            $lastComment = $this->findOrFail($commentLastId);
            if(empty($postUuid))
            {
                $post = app(PostRepository::class)->findOrFailById($lastComment->post_id);
                $postUuid = $post->post_uuid;
            }
            $comments = $comments->where('comment_top_id' , $commentTopId)->where('comment_id' , '>' , $lastComment->comment_id);
        }else{
            $comments = $comments->where('comment_top_id' , $commentTopId);
        }
        if(!empty($queryTime))
        {
            $comments = $comments->where('comment_created_at' , '<=' , $queryTime);
        }
        $comments = $comments->with('likers');
        $comments = $comments->orderBy('comment_id')
            ->limit($this->perPage)->get();
        $userIds = $comments->pluck('user_id')->merge($comments->pluck('comment_to_id'))->unique();
        $users = app(UserRepository::class)->findByMany($userIds->all());
        $comments->each(function($item , $key) use ($postUuid , $users){
            $item->post_uuid  = $postUuid;
            $item->owner = $users->where('user_id' , $item->user_id)->first();
            $item->toer = $users->where('user_id' , $item->comment_to_id)->first();
        });
        return $comments;
    }

    public function findByLocateCommentId($request , $commentId)
    {
        $model = $this->model;
        $comment = $this->findOrFail($commentId);
        $postUuid = $request->input('post_uuid' , '');
        if(empty($postUuid))
        {
            $post = app(PostRepository::class)->findOrFailById($comment->post_id);
            $postUuid = $post->post_uuid;
        }
        if($comment->comment_top_id==0)
        {
            $locateCommentCount = $model->where('comment_top_id' , $comment->comment_top_id)
                                    ->where('post_id' ,$comment->post_id)
                                    ->where('comment_like_num' , '>' ,$comment->comment_like_num)
                                    ->count();
            $locateEqualCommentCount = $model->where('comment_top_id' , $comment->comment_top_id)
                ->where('post_id' ,$comment->post_id)
                ->where('comment_like_num' , $comment->comment_like_num)
                ->where($this->model->getCreatedAtColumn() , '>=' , $comment->{$this->model->getCreatedAtColumn()})
                ->count();
            $locateCommentCount = $locateCommentCount+$locateEqualCommentCount;
            $currentPage = $request->input($this->pageName , ceil($locateCommentCount/$model->perPage));
            $currentPage = $currentPage<1?1:$currentPage;
            $comments = $model->where('comment_top_id' , $comment->comment_top_id)
                ->where('post_id' ,$comment->post_id)
                ->with('translations')
                ->with('likers')
                ->with('owner')
                ->orderBy('comment_like_num' , 'DESC')
                ->orderBy($this->model->getCreatedAtColumn() , 'DESC')
                ->paginate($this->perPage , ['*'] , $this->pageName , $currentPage);
            $commentIds = $comments->pluck('comment_id')->all();//��ȡcomment Id
            $subCommentsCount = $this->getChildCountByCommentIds($commentIds);
            $comments->each(function ($item, $key) use ($postUuid , $subCommentsCount) {
                $item->post_uuid = $postUuid;
                $item->subCommentsCount = collect($subCommentsCount->where('comment_top_id',$item->comment_id)->first())->get('num' , 0);
            });
            return PostCommentCollection::collection($comments);
        }else{
            $locateCommentCount = $model->where('comment_top_id' , $comment->comment_top_id)->where('comment_id' , '<' ,$commentId)->count();
            $currentPage = $request->input($this->pageName , ceil($locateCommentCount/$model->perPage));
            $currentPage = $currentPage<1?1:$currentPage;
            $queryTime = $request->get('query_time' , '');
            $queryTime = empty($queryTime)?$queryTime:date('Y-m-d H:i:s' , strtotime($queryTime));
            $comments = $model->where('comment_top_id' , $comment->comment_top_id)
                ->with('translations')
                ->with('likers')
                ->with('owner')
                ->with('to')
                ->orderBy('comment_id')
                ->offset(($currentPage-1)*$this->perPage)
                ->limit($this->perPage)
                ->get();
            $comments->each(function($item , $key) use ($postUuid){
                $item->post_uuid  = $postUuid;
            });
            $topComment = $this->allWithBuilder()->with('likers')->with('owner')->where('comment_id' , $comment->comment_top_id)->first();
            $topComment->post_uuid = $postUuid;
            $topComment->topTwoComments = $comments;
            return new PostCommentCollection($topComment);
        }
    }

    public function findByUserId($request , $user_id)
    {
        $comments = $this->allWithBuilder();
        $comments = $comments->where('user_id' , $user_id);
        $comments = $comments->with('likers')
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

    public function findByCommentIds(array $comment_ids , $type='comment')
    {
        $comments = $this->allWithBuilder();
        if(auth()->check())
        {
            $comments = $comments->with(['likers'=>function($query){
                $query->where('users.user_id' , auth()->id());
            }]);
        }
        if($type=='comment')
        {
            $comments = $comments->with(['parent'=>function($q){
                $q->with('translations')->with('owner');
            }]);
        }
        return $comments->whereIn('comment_id' , $comment_ids)
            ->with('owner')
            ->with(['post'=>function($q){
                $q->with('translations')->withTrashed();
            }])->get();
    }


    public function topTwoComments($commentIds , $queryTime=null)
    {
        $topTwoCommentQuery = PostComment::whereIn('comment_top_id',$commentIds)
            ->where('comment_comment_p_id' , '>' , 0);
        if(!empty($queryTime))
        {
            $topTwoCommentQuery = $topTwoCommentQuery->where('comment_created_at' , '<=' , $queryTime);
        }
        $topTwoCommentQuery = $topTwoCommentQuery->orderBy('comment_top_id')
            ->select(DB::raw('*,@comment := NULL ,@rank := 0'))
            ->orderBy('comment_id');
        $topTwoCommentQuery = DB::table(DB::raw("({$topTwoCommentQuery->toSql()}) as b"))
            ->mergeBindings($topTwoCommentQuery->getQuery())
            ->select(DB::raw('b.*,IF (
                    @comment = b.comment_top_id ,@rank :=@rank + 1 ,@rank := 1
                ) AS rank,
                @comment := b.comment_top_id'));
        $topTwoCommentIds = DB::table( DB::raw("({$topTwoCommentQuery->toSql()}) as f_c") )
            ->mergeBindings($topTwoCommentQuery)
            ->where('rank','<',3)->select('c.comment_id')->pluck('comment_id')->toArray();

        $postComments = PostComment::whereIn('comment_id',$topTwoCommentIds)
            ->with('translations');
        if(auth()->check())
        {
            $postComments = $postComments->with(['likers'=>function($query){
                $query->where('users.user_id' , auth()->id());
            }]);
        }
        return $postComments->get();
    }

    public function getChildCountByCommentIds($commentIds , $queryTime=null)
    {
        $postComment = PostComment::select('comment_top_id', DB::raw('COUNT(comment_id) as num'))
            ->whereIn('comment_top_id' , $commentIds);
        if(!empty($queryTime))
        {
            $postComment = $postComment->where('comment_created_at' , '<=' , $queryTime);
        }
        return $postComment->groupBy('comment_top_id')->get();
    }
}
