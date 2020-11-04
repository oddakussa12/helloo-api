<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Resources\PostCollection;
use App\Resources\PostCommentCollection;
use Fenos\Notifynder\Models\Notification;
use App\Resources\NotificationCollection;
use App\Repositories\Contracts\PostRepository;
use App\Resources\NotificationPaginateCollection;
use App\Repositories\Contracts\CategoryRepository;
use App\Resources\NotificationPostCommentCollection;
use App\Repositories\Contracts\PostCommentRepository;

class NotificationController extends BaseController
{
    /**
     * @var CategoryRepository
     */
    private $notification;


    public function index(Request $request)
    {
        $postIds = array();
        $commentIds = array();
        $type = $request->get('type' , 'global');
        $appends['type'] = $type;
        if($type=='global')
        {
            $message = Notification::where('to_type'  , 'global')->where('expires_at' , '>=' , Carbon::now())->orderByDesc('expires_at')->get();
            if(auth()->check()&&auth()->user()->user_name=='admin')
            {
                $user = auth()->user();
                $message_ext = Notification::where('to_id' , $user->user_id)
                    ->whereIn('category_id', [4 , 7 , 8])
                    ->orderBy('created_at', 'desc')
                    ->orderBy('read', 'asc')
                    ->paginate(5 , ['*'] , 'notice_page');
                $message_ext= $message_ext->appends($appends);
                return array(
                    'global'=>$message,
                    'admin'=>NotificationCollection::collection($message_ext),
                );
            }
            return array(
                'global'=>$message);
        }elseif ($type=='like'){
            if(auth()->check())
            {
                $user = auth()->user();
                $message = Notification::where('to_id' , $user->user_id)
                    ->with('from')
                    ->with('category')
                    ->where('category_id', 3)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('read', 'asc')
                    ->paginate(10 , ['*'] , 'notice_page');
                $message= $message->appends($appends);
                $message->each(function($item , $key) use (&$postIds , &$commentIds){
                    $extra = $item->extra;
                    array_push($postIds , $extra['post_id']);
                    array_push($commentIds , $extra['comment_id']);
                });
                $comments = PostCommentCollection::collection(app(PostCommentRepository::class)->findByCommentIds($commentIds , $type));
                $message->each(function($item , $key) use ($comments){
                    $extra = $item->extra;
                    $item->detail = $comments->where('comment_id' , $extra['comment_id'])->values()->first();
                });
                return NotificationCollection::collection($message);
            }

        }elseif ($type=='comment'){
            if(auth()->check())
            {
                $user = auth()->user();
                $message = Notification::where('to_id' , $user->user_id)->with('category')
                    ->whereIn('category_id', [5 , 6])
                    ->orderBy('created_at', 'desc')
                    ->orderBy('read', 'asc')
                    ->paginate(10 , ['*'] , 'notice_page');
                $message= $message->appends($appends);
                $message->each(function($item , $key) use (&$postIds , &$commentIds , $user){
                    $extra = $item->extra;
                    $item->to = $user;
                    array_push($postIds , $extra['post_id']);
                    array_push($commentIds , $extra['comment_id']);
                });
                $comments = PostCommentCollection::collection(app(PostCommentRepository::class)->findByCommentIds($commentIds));
                $message->each(function($item , $key) use ($comments){
                    $extra = $item->extra;
                    $item->detail = $comments->where('comment_id' , $extra['comment_id'])->values()->first();
                });
                return NotificationCollection::collection($message);
            }
        }else{
            return $this->response->errorNotFound();
        }
        return array();
    }

    public function home()
    {
        $user = auth()->user();
        $message = Notification::where('to_id' , $user->user_id)
            ->with('from')
            ->orderBy('created_at', 'desc')
            ->orderBy('read', 'asc')
            ->paginate(10 , ['*'] , 'notice_page');
        return NotificationPaginateCollection::collection($message);
    }


    public function count()
    {
        $like_count= 0;
        $comment_count= 0;
        $global= 0;
        if(auth()->check())
        {
            if(auth()->user()->user_name=='admin')
            {
                $global = auth()->user()->getNotificationRelation()
                    ->where('read', 0)
                    ->whereIn('category_id', [4 , 7])
                    ->count();
            }
            $like_count = auth()->user()->getNotificationRelation()
                ->where('read', 0)
                ->where('category_id', 3)
                ->count();
            $comment_count = auth()->user()->getNotificationRelation()
                ->where('read', 0)
                ->whereIn('category_id', [5 , 6])
                ->count();
            $follow_count = auth()->user()->getNotificationRelation()
                ->where('read', 0)
                ->where('category_id', 1)
                ->count();
        }
        return $this->response->array(array('count'=>$like_count+$comment_count+$global , 'like_count'=>$like_count , 'comment_count'=>$comment_count ,'follow_count'=>$follow_count,'global'=>$global));
    }

    public function unreadCount()
    {
        $count= 0;
        if(auth()->check())
        {
            $count = auth()->user()->getNotificationRelation()
                ->where('read', 0)
                ->count();
        }
        return $this->response->array(array('count'=>$count));
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return 'store';
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        auth()->user()->getNotificationRelation()->where(function($query) use ($id){
            $query->where('id' , $id);
        })->first()->read();
        return $this->response->noContent();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return '123';
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
        return 'update';
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

    public function read($id)
    {
        $notification = auth()->user()->getNotificationRelation()->where(function($query) use ($id){
            $query->where('id' , $id)
                ->where('read' , 0);
//                ->whereNull('expires_at');
//                ->orWhere('expires_at', '>=', Carbon::now());
        })->first();
        if($notification)
        {
            $notification->read();
//            return $this->response->errorNotFound();
        }

        return $this->response->noContent();
    }

    public function readAll($type='')
    {
        $user = auth()->user();
        Notification::where('to_id' , $user->user_id)->where('read' , 0)->update(['read' => 1]);
        return $this->response->noContent();
        if($type=='like')
        {
            $notices = auth()->user()->getNotificationRelation()->where(function($query){
                $query->where('category_id' , 3)
                    ->where('read' , 0);
            })->get();
            foreach ($notices as $notice)
            {
                if($notice->read==0)
                {
                    $notice->read();
                }
            }
        }elseif($type=='comment'){
            $notices = auth()->user()->getNotificationRelation()->where(function($query){
                $query->whereIn('category_id' , [5 , 6])
                    ->where('read' , 0);
            })->get();
            foreach ($notices as $notice)
            {
                if($notice->read==0)
                {
                    $notice->read();
                }
            }
        }elseif($type=='follow'){
            $notices = auth()->user()->getNotificationRelation()->where(function($query){
                $query->where('category_id' , 1)
                    ->where('read' , 0);
            })->get();
            foreach ($notices as $notice)
            {
                if($notice->read==0)
                {
                    $notice->read();
                }
            }
        }
        return $this->response->noContent();
    }

    public function detail($id)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $notification = Notification::where('id' , $id)->where('to_id' , $userId)->first();
        if(!$notification)
        {
            return $this->response->errorNotFound();
        }
        $notification->read==0&&$notification->read();
        $detail = array();
        $extra = $notification->extra;
        $category_id = $notification->category_id;
//        $from = app(UserRepository::class)->findOrFail($notification->from_id);
        switch ($category_id)
        {
            case 1://user.following
                //{"follow_user_id":967857,"followed_user_id":914379}
//                $txt = trans('notifynder.user.following');
                return $this->response->accepted();
                break;
            case 3://user.like
                //{"comment_id":44148,"post_id":998}
                $commentId = $extra['comment_id'];
                $postId = $extra['post_id'];
//                $post = app(PostRepository::class)->find($postId);
//                $postComment = app(PostCommentRepository::class)->find($commentId);
                return $this->response->accepted();
                break;
            case 5://user.post_comment
                //{"comment_id":44143,"post_id":998}
                $commentId = $extra['comment_id'];
                $postId = $extra['post_id'];
                $postComment = app(PostCommentRepository::class)->allWithBuilder()->with('owner')
                    ->with(['post'=>function($q){
                        $q->with('translations')->withTrashed();
                    }])->withTrashed()->find($commentId);
                return new NotificationPostCommentCollection($postComment);
                break;
            case 6://user.comment
                //{"comment_id":44191,"post_id":1003,"comment_comment_p_id":44177}
                $commentId = $extra['comment_id'];
                $commentPId = $extra['comment_comment_p_id'];
                $postId = $extra['post_id'];

                $postComment = app(PostCommentRepository::class)->allWithBuilder()->with('owner')
                    ->with(['post'=>function($q){
                        $q->with('translations')->withTrashed();
                    }])->withTrashed()->find($commentId);
                $postComment->parentComment = app(PostCommentRepository::class)->allWithBuilder()->where('comment_id' , $commentPId)->withTrashed()->first();
                return new NotificationPostCommentCollection($postComment);
                break;
            case 9://user.post_like
            case 10://user.post_dislike
                //{"post_id":9768}
                $postId = $extra['post_id'];
                $post = app(PostRepository::class)->find($postId);
                return new PostCollection($post);
                break;
            default:
                return $this->response->accepted();
                break;
        }
        return $detail;
    }

    public function test()
    {

    }
}
