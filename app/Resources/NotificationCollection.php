<?php


namespace App\Resources;

use App\Repositories\Contracts\PostCommentRepository;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Http\Resources\Json\Resource;

class NotificationCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'read' => $this->read,
            'text' => $this->text,
            'from_id' => $this->from_id,
            'to_type' => $this->to_type,
            'title' => $this->when(!empty($this->category->name)&&$this->category->name=='global.first' , trans('notifynder.title')),
            'type' => $this->category->name,
            'extra' => $this->extra,
            'detail'=>$this->detail,
            'created_at'=>optional($this->created_at)->toDateTimeString(),
        ];
    }
//
//    private function details()
//    {
//        $detail = array();
//        if($this->to_type!='global')
//        {
//            $extra = $this->extra;
//            switch ($this->category->name)
//            {
//                case 'user.like':
//                case 'user.post_comment':
//                    try{
//                        $detail['comment'] = app(PostCommentRepository::class)->find($extra['comment_id']);
//                        if(empty($detail['comment'] ))
//                        {
//                            $detail = false;
//                        }else{
//                            $detail['comment'] = new PostCommentCollection($detail['comment']);
//                        }
//                    }catch (\Exception $e)
//                    {
//                        $detail = false;
//                    }
//                    break;
//                case 'admin.comment_notice':
//                case 'admin.like_notice':
//                    try{
//                        $detail['comment'] = app(PostCommentRepository::class)->find($extra['comment_id']);
//                        if(empty($detail['comment'] ))
//                        {
//                            $detail = false;
//                        }else{
//                            $detail['comment'] = new PostCommentCollection($detail['comment']);
//                        }
//                    }catch (\Exception $e)
//                    {
//                        $detail = false;
//                    }
//                    break;
//                case 'user.comment':
//                    try{
//                        $detail['comment'] = app(PostCommentRepository::class)->find($extra['comment_id']);
//                        if(empty($detail['comment'] ))
//                        {
//                            $detail = false;
//                        }else{
//                            $detail['comment'] = new PostCommentCollection($detail['comment']);
//                        }
//                    }catch (\Exception $e){
//                        $detail = false;
//                    }
//                    break;
//            }
//        }
//        return $detail;
//
//    }
}