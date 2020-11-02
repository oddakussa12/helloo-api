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
    const QUEUE_CUSTOM_POST_TRANSLATION="custom_post_translation";

    // 用户自定义表情
    const CUSTOM_USER_EMOJI='custom_user_emoji_';

    // ES 队列
    const QUEUE_ES_POST="post_es";
    const QUEUE_ES_TOPIC="topic_es";
    const QUEUE_ES_USER="user_es";

    // 是否显示用户浏览量详情页
    const USER_MAIN_VISIT_STATUS=1;

    // 融云回调
    const QUEUE_RY_CHAT='store_ry_msg';

    // 当等于 true时 执行 融云聊天写入数据库操作
    const QUEUE_RY_CHAT_SWITCH=1;

    // 自定义融云 OBJECTNAME

    // 访问用户主页
    const RY_OBJECT_NAME_USER_MAIN='USER_MAIN';

    // 好友关系 升级
    const RY_OBJECT_NAME_HEART_UPGRADE='HEART_UPGRADE';

    // 好友关系


    //队列服务  redis  sqs
    const QUEUE_PUSH_TYPE='redis';

    // 融云聊天-加好友队列
    const QUEUE_RY_CHAT_FRIEND="friend";

    // 发送好友请求
    const QUEUE_PUSH_FRIEND="friend_request";

    // 签到
    const QUEUE_FRIEND_SIGN_IN='friend_sign_in';

    // 好友关系升级
    const QUEUE_FRIEND_LEVEL='friend_level';

    // 好友访问主页统计
    const QUEUE_FRIEND_VISIT='friend_visit';


    // REDIS 相关

    //每日获取心❤的数量
    const DAY_UPPER_STAR=5;

    // 每聊20句可以升一个心
    const CHAT_SUM_STAR=20;

    // 签到
    const RY_CHAT_FRIEND_SIGN_IN="friend_sign_in_";

    // 是否是朋友关系
    const RY_CHAT_FRIEND_IS_FRIEND="friend_is_friend_";

    // 是否有特殊好友关系 如 情侣 基友等
    const RY_CHAT_FRIEND_RELATIONSHIP="friend_relationship_";

    // 好友主页 数据缓存key
    const FRIEND_RELATIONSHIP_MAIN="friend_relationship_main_";

    // 个人主页 top5 数据缓存
    const FRIEND_RELATIONSHIP_HOME_TOP='friend_relationship_home_top_';




    // 特殊关系对应表 如 情侣  闺蜜等
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
        self::RELATION_LOVER=>1,
        self::RELATION_FRIEND=>5,
        self::RELATION_SISTER=>5,
        self::RELATION_S=>5,
    ];

}
