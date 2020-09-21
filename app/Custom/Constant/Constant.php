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

    // 翻译 队列
    const QUEUE_POST_TRANSLATION="post_translation";

    // ES 队列
    const QUEUE_ES_POST="post_es";
    const QUEUE_ES_TOPIC="topic_es";
    const QUEUE_ES_USER="user_es";












    // REDIS 相关
    // 好友关系

    //每日获取心❤的数量
    const DAY_UPPER_STAR=5;

    // 签到
    const RY_CHAT_FRIEND_SIGN_IN="ry_chat_friend_sign_in_";

    // 是否是朋友关系
    const RY_CHAT_FRIEND_IS_FRIEND="ry_chat_friend_is_friend_";

    // 是否有特殊好友关系 如 情侣 基友等
    const RY_CHAT_FRIEND_RELATIONSHIP="ry_chat_friend_relationship_";

    // 是否有特殊好友关系 如 情侣 基友等
    const RY_CHAT_FRIEND_RELATION_CREATE="ry_chat_friend_relation_create_";



}
