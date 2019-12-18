<?php

namespace App\Http\Controllers\V1;

use App\Services\JpushService;

class TestController extends BaseController
{
    //
    public function index()
    {

        echo basename(base_path());
        echo '<br />';
        echo config('common.app_dir');die;
        var_dump(domain()!=domain(config('app.url')));die;
        echo locale();
        echo trans('notifynder.user.private_message' , [] , 'jas');
//        JpushService::androidOrIosPush(array(
//            'platform'=>'ios',
//            'title'=>'测试标题',
//            'content'=>'测试内容'.date('Y-m-d H:i:s' , time()),
//            'builderId'=>1,
//            'extras'=>array('type'=>'privateChat' , 'userId'=>1 , 'commentId'=>1),
//            'type'=>3,
//        ));
//        JpushService::androidOrIosPush(array(
//            'platform'=>'ios',
//            'title'=>'您有一条新的评论',
//            'content'=>'评论内容'.date('Y-m-d H:i:s' , time()),
//            'builderId'=>1,
//            'extras'=>array('type'=>'comment' , 'url'=>"https://yooul.com/inbox/myreplies"),
//            'type'=>3,
//        ));
//        JpushService::androidOrIosPush(array(
//            'platform'=>'ios',
//            'title'=>'您有一条新的点赞',
//            'content'=>'点赞内容'.date('Y-m-d H:i:s' , time()),
//            'builderId'=>1,
//            'extras'=>array('type'=>'like' , 'url'=>"https://yooul.com/inbox/mylikes"),
//            'type'=>2,
//            'registrationId'=>'141fe1da9ec229cd547'
//        ));
//        JpushService::androidOrIosPush(array(
//            'platform'=>'ios',
//            'title'=>'您有一条私信',
//            'content'=>'私信内容'.date('Y-m-d H:i:s' , time()),
//            'builderId'=>1,
//            'extras'=>array('type'=>'privatechat' , 'user_id'=>"8138"),
//            'type'=>2,
//            'registrationId'=>'141fe1da9ec229cd547'
//        ));
    }
    
}
