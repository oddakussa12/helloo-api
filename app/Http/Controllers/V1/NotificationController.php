<?php

namespace App\Http\Controllers\V1;

use App\Resources\PostCollection;
use App\Resources\PostCommentCollection;
use Carbon\Carbon;
use App\Models\User;
use App\Models\PostComment;
use Illuminate\Http\Request;
use App\Notifications\CommentLiked;
use App\Resources\NotificationCollection;
use Fenos\Notifynder\Models\Notification;
use App\Repositories\Contracts\PostCommentRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\CategoryRepository;

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
                $message_ext = auth()->user()->getNotificationRelation()
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
                $message = auth()->user()->getNotificationRelation()
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
                $comments = PostCommentCollection::collection(app(PostCommentRepository::class)->findByCommentIds($commentIds));
                $message->each(function($item , $key) use ($comments){
                    $extra = $item->extra;
                    $item->detail = $comments->where('comment_id' , $extra['comment_id'])->values()->first();
                });
                return NotificationCollection::collection($message);
            }

        }elseif ($type=='comment'){
            if(auth()->check())
            {
                $message = auth()->user()->getNotificationRelation()->with('category')->with('from')->with('to')
                    ->whereIn('category_id', [5 , 6])
                    ->orderBy('created_at', 'desc')
                    ->orderBy('read', 'asc')
                    ->paginate(10 , ['*'] , 'notice_page');
                $message= $message->appends($appends);
                $message->each(function($item , $key) use (&$postIds , &$commentIds){
                    $extra = $item->extra;
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

    public function readAll($type)
    {
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
        $notification = auth()->user()->getNotificationRelation()->where(function($query) use ($id){
            $query->where('id' , $id);
        })->first();
        if(!$notification)
        {
            return $this->response->errorNotFound();
        }
        $notification->read();
        $detail = array();
        $extra = $notification->extra;
        switch ($notification->category->name)
        {
            case 'user.like':
            case 'user.post_comment':
                try{
                    $comment = app(PostCommentRepository::class)->find($extra['comment_id']);
                    $post = app(PostRepository::class)->find($extra['post_id']);
                }catch (\Exception $e){
                    $detail = array();
                    break;
                }
                if(empty($comment)||empty($post))
                {
                    $detail = array();
                }else{
                    $detail = array('comment'=>new PostCommentCollection($comment) , 'post'=>new PostCollection($post));
                }
                break;
            case 'user.comment':
                try{
                    $post= app(PostRepository::class)->find($extra['post_id']);
                    $comment = app(PostCommentRepository::class)->find($extra['comment_id']);
                    $p_comment = app(PostCommentRepository::class)->find($extra['comment_comment_p_id']);
                }catch (\Exception $e)
                {
                    $detail = array();
                    break;
                }
                if(empty($post)||empty($comment)||empty($p_comment))
                {
                    $detail = array();
                }else{
                    $detail = array('comment'=>new PostCommentCollection($comment) , 'post'=>new PostCollection($post) , 'p_comment '=>new PostCommentCollection($p_comment));
                }
                break;
        }
        return $detail;
    }

    public function test()
    {

        //\Notification::send(User::find(2), new CommentLiked(PostComment::find(40897)));
        User::find(2)->notify(new CommentLiked(PostComment::find(40897)));
    }
}
