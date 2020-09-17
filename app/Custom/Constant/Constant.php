<?php
namespace App\Custom\Constant;

class Constant {

    //话题推送阈值
    const TOPIC_PUSH_THRESHOLD = 2;

    //话题推送给关注者
    const QUEUE_PUSH_TOPIC='topic_push';

    //发送帖子推送粉丝
    const QUEUE_PUSH_POST='post_fans';

    // PUSH 推送
    const QUEUE_PUSH_NAME="op_jpush";

    // ES 队列
    const QUEUE_ES_POST="post_es";
    const QUEUE_ES_TOPIC="topic_es";
    const QUEUE_ES_USER="user_es";


}
