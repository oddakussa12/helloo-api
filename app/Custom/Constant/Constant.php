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




    // 融云回调
    const QUEUE_RY_CHAT='store_ry_msg_tsm';

    const QUEUE_RY_CHAT_SWITCH=0;


    // 好友关系


    const QUEUE_PUSH_TYPE='redis';

    // 融云聊天-加好友队列
    const QUEUE_RY_CHAT_FRIEND="friend_tsm";

    // 发送好友请求
    const QUEUE_PUSH_FRIEND="friend_request";

    // 签到
    const QUEUE_FRIEND_SIGN_IN='friend_sign_in';

    // 好友关系升级
    const QUEUE_FRIEND_LEVEL='friend_level';


    // REDIS 相关

    //每日获取心❤的数量
    const DAY_UPPER_STAR=5;
    const CHAT_SUM_STAR=20;

    // 签到
    const RY_CHAT_FRIEND_SIGN_IN="ry_chat_friend_sign_in_";

    // 是否是朋友关系
    const RY_CHAT_FRIEND_IS_FRIEND="ry_chat_friend_is_friend_";

    // 是否有特殊好友关系 如 情侣 基友等
    const RY_CHAT_FRIEND_RELATIONSHIP="ry_chat_friend_relationship_";

    // 好友主页 数据缓存key
    const FRIEND_RELATIONSHIP_MAIN="friend_relationship_main_";




    const RELATION_LOVER=1;
    const RELATION_FRIEND=2;
    const RELATION_SISTER=3;
    const RELATION_S=4;

    public static $relation = [
        self::RELATION_LOVER,
        self::RELATION_FRIEND,
        self::RELATION_SISTER,
        self::RELATION_S,
    ];
    public static $relationSum = [
        self::RELATION_LOVER=>2,
        self::RELATION_FRIEND=>5,
        self::RELATION_SISTER=>5,
        self::RELATION_S=>5,
    ];

}
