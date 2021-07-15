<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Models\Business\Comment;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\StoreGoodsCommentRequest;
use App\Repositories\Contracts\GoodsRepository;
use Illuminate\Support\Facades\Log;

class GoodsCommentsController extends BaseController
{
    /**
     * @note 商品评论
     * @datetime 2021-07-12 17:52
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    public function index(Request $request)
    {
        $appends = array();
        $userId = intval($request->input('user_id' , 0));
        $goodsId = strval($request->input('goods_id' , ''));
        if($userId>0)
        {
            $user = app(UserRepository::class)->findByUserId($userId);
            if(blank($user)||$user->get('user_shop' , 0)!=1)
            {
                return $this->response->errorNotFound('Sorry, this Shop does not exist!');
            }
            $appends['user_id'] = $userId;
            $comments = Comment::where('verified' , 1)->where('owner' , $userId)->where('p_id' , 0)->orderByDesc('created_at')->paginate(10);
            $comments = $comments->appends($appends);
        }elseif (!empty($goodsId))
        {
            $goods = app(GoodsRepository::class)->find($goodsId);
            if(empty($goods))
            {
                return $this->response->errorNotFound('Sorry, this goods does not exist!');
            }
            $appends['goods_id'] = $goodsId;
            $comments = Comment::where('verified' , 1)->where('goods_id' , $goodsId)->where('p_id' , 0)->orderByDesc('created_at')->paginate(10);
            $comments = $comments->appends($appends);
        }else{
            return $this->response->errorBadRequest();
        }
        $userIds = $comments->pluck('user_id')->unique()->toArray();
        if(!empty($userIds))
        {
            $users = app(UserRepository::class)->findByUserIds($userIds);
            $comments->each(function($comment) use ($users){
                $comment->user = new UserCollection($users->where('user_id' , $comment->user_id)->first());
            });
        }
        return AnonymousCollection::collection($comments);
    }

    /**
     * @note 商品评论回复
     * @datetime 2021-07-12 17:52
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    public function reply(Request $request)
    {
        $pId = intval($request->input('p_id' , 0));
        $pComment = Comment::where('comment_id' , $pId)->first();
        if(empty($pComment)&&$pComment->verified!=1)
        {
            return $this->response->errorNotFound('Sorry, the commented resource does not exist!');
        }
        $comments = Comment::where('p_id' , $pId)->select('comment_id' , 'goods_id' , 'owner' ,'user_id' , 'p_id' , 'content' , 'media' , 'created_at')->orderByDesc('created_at')->limit(2)->get();
        $userIds = $comments->pluck('user_id')->unique()->toArray();
        if(!empty($userIds))
        {
            $users = app(UserRepository::class)->findByUserIds($userIds);
            $comments->each(function($comment) use ($users){
                $comment->user = new UserCollection($users->where('user_id' , $comment->user_id)->first());
            });
        }
        return AnonymousCollection::collection($comments);
    }

    /**
     * @note 商品评论
     * @datetime 2021-07-12 17:52
     * @param StoreGoodsCommentRequest $request
     * @return AnonymousCollection|void
     */
    public function store(StoreGoodsCommentRequest $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $goodsId = strval($request->input('goods_id' , ''));
        $pId = intval($request->input('p_id' , 0));
        $type = $request->input('type' , '');
        $content = strval($request->input('content' , ''));
        $media = $request->input('media' , array());
        $point = intval($request->input('point' , 0));
        $service = round($request->input('service' , 0) , 1);
        $quality = round($request->input('quality' , 0) , 1);
        $commentId = app('snowflake')->id();
        $goods = app(GoodsRepository::class)->find($goodsId);
        if(empty($goods))
        {
            return $this->response->errorNotFound('Sorry, this goods does not exist!');
        }
        $now = date('Y-m-d H:i:s');
        if($type=='reply')
        {
            $pComment = Comment::where('comment_id' , $pId)->first();
            if(empty($pComment))
            {
                return $this->response->errorNotFound('Sorry, the commented resource does not exist!');
            }
            if($userId!=$pComment->owner)
            {
                return $this->response->errorForbidden('Sorry, this goods belongs to others!');
            }
            if($pComment->child_comment>=2)
            {
                return $this->response->errorForbidden('Sorry, you can only reply to two comments!');
            }
            try{
                DB::beginTransaction();
                $commentResult = DB::table('comments')->insert(array(
                    'comment_id'=>$commentId,
                    'goods_id'=>$goodsId,
                    'owner'=>$goods->user_id,
                    'user_id'=>$userId,
                    'to_id'=>$pComment->user_id,
                    'p_id'=>$pId,
                    'top_id'=>$pId,
                    'content'=>$content,
                    'type'=>$type,
                    'step'=>intval($pComment->step)+1,
                    'verified'=>1,
                    'verified_at'=>$now,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ));
                if(!$commentResult)
                {
                    abort(500 , 'comment reply failed!');
                }
                $pCommentResult = DB::table('comments')->where('comment_id' , $pId)->increment('child_comment');
                if($pCommentResult<=0)
                {
                    abort(500 , 'comment reply update failed!');
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('comment_replay_fail' , array(
                    'code'=>$e->getCode(),
                    'message'=>$e->getMessage(),
                    'data'=>$request->all(),
                    'user_id'=>$userId
                ));
            }
        }else{
            if($goods->user_id==$userId)
            {
                return $this->response->errorForbidden('Sorry, you cannot comment on your own goods!');
            }
            $media = array_filter($media , function($v){
                if(isset($v['type']))
                {
                    if($v['type']=='video')
                    {
                        return isset($v['url'])&&isset($v['video'])&&isset($v['height'])&&isset($v['width']);
                    }elseif ($v['type']=='image')
                    {
                        return isset($v['height'])&&isset($v['width'])&&isset($v['url']);
                    }
                }
                return false;
            });
            $media = array_map(function ($v){
                if($v['type']=='video')
                {
                    return array(
                        'video'=>$v['video'],
                        'url'=>$v['url'],
                        'height'=>$v['height'],
                        'width'=>$v['width'],
                        'type'=>'video',
                    );
                }else{
                    return array(
                        'height'=>$v['height'],
                        'width'=>$v['width'],
                        'url'=>$v['url'],
                        'type'=>'image',
                    );
                }
            } , $media);
            $data = array(
                'comment_id'=>$commentId,
                'goods_id'=>$goodsId,
                'owner'=>$goods->user_id,
                'user_id'=>$userId,
                'to_id'=>$goods->user_id,
                'content'=>$content,
                'type'=>$type,
                'service'=>$service,
                'quality'=>$quality,
                'point'=>$point,
                'created_at'=>$now,
                'updated_at'=>$now,
            );
            !empty($media)&&$data['media'] = \json_encode($media, JSON_UNESCAPED_UNICODE);
            DB::table('comments')->insert($data);
        }
        $comment = Comment::where('comment_id' , $commentId)->first();
        if(empty($comment))
        {
            return $this->response->errorInternal();
        }
        return new AnonymousCollection($comment);
    }

}
