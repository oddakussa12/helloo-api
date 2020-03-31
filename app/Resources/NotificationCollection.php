<?php


namespace App\Resources;

use Carbon\Carbon;
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
            'from' =>$this->when($request->input('type')=='like' , function(){
                return new UserCollection($this->from);
            }),
            'created_at'=>optional($this->created_at)->toDateTimeString(),
            'format_created_at'=>$this->getPostFormatCreatedAt(),
        ];
    }

    private function getPostFormatCreatedAt()
    {
        $locale = locale();
        if($locale=='zh-CN')
        {
            Carbon::setLocale('zh');
        }elseif ($locale=='zh-TW'||$locale=='zh-HK')
        {
            Carbon::setLocale('zh_TW');
        }else{
            $locale = 'en';
            Carbon::setLocale($locale);
            $translator = \Carbon\Translator::get($locale);
            $translator->setMessages($locale , [
                'minute' => ':count m|:count m',
                'hour' => ':count h|:count h',
                'day' => ':count d|:count d',
                'month' => ':count mo|:count mo',
                'year' => ':count yr|:count yr',
            ]);
        }
        return Carbon::parse($this->created_at)->diffForHumans();
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