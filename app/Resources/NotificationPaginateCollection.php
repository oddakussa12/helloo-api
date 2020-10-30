<?php


namespace App\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\Resource;

class NotificationPaginateCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'read' => $this->read,
            'text' => $this->getText(),
            'from_id' => $this->from_id,
            'category_id' => $this->category_id,
//            'to_type' => $this->to_type,
//            'title' => $this->when(!empty($this->category->name)&&$this->category->name=='global.first' , trans('notifynder.title')),
//            'type' => $this->category->name,
            'extra' => $this->extra,
//            'detail'=>$this->detail,
//            'from' =>$this->when($request->input('type')=='like' , function(){
//                return new UserCollection($this->from);
//            }),
            'from'=> new UserCollection($this->from),
            'created_at'=>optional($this->created_at)->toDateTimeString(),
            'format_created_at'=>$this->getPostFormatCreatedAt(),
        ];
    }

    public function getText()
    {
        switch ($this->category_id)
        {
            case 1:
                $txt = trans('notifynder.user.follow_user');
                break;
            case 3:
                $txt = trans('notifynder.user.like');
                break;
            case 5:
                $txt = trans('notifynder.user.post_comment');
                break;
            case 6:
                $txt = trans('notifynder.user.comment');
                break;
            case 9:
            case 10:
                $txt = trans('notifynder.user.post_like');
                break;
            default:
                $txt = "You have a new message!";
                break;
        }
        return $txt;
    }

    private function getPostFormatCreatedAt()
    {
        return dateTrans($this->created_at);
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