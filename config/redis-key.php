<?php
return [
    "user"=>[
        'user_name'=>'user.name',//用户账户
        'user_friend'=>'user_friend',//用户好友
        'user_email'=>'user.email',//用户邮箱
        'user_visit'=>'user_visit',//用户首页访问次数
        'score_rank'=>'user.score.rank',//用户积分排行
        'follow_me'=>'user.follow.me',//用户被关注数量
        'my_follow'=>'user.my.follow',//用户被关注数量
        'profile_likes'=>'user.profile.likes',//用户主页点赞数
        'posts'=>'user.posts',//用户发帖数
        'post_comments'=>'user.post.comments',//用户评论数
        'post_likes'=>'user.post.likes',//用户贴子点赞数
        'post_dislikes'=>'user.post.dislikes',//用户贴子踩数
        'post_comment_likes'=>'user.post.comment.likes',//用户评论点赞数
        'ry_update_online_status'=>'ry.update.online.status',//融云回调用户状态用户
    ],
    "post"=>[
        "post_index_essence"=>'post_index_essence',//精华贴子(下拉)
        "post_index_essence_customize"=>'post_index_essence_manual',//自定义精华贴子(下拉)
        "post_index_top"=>'post_index_top',//置顶贴子
        "post_index_new"=>'post_index_new',//最新贴子
        "post_index_rate"=>'post_index_rate',//最热贴子
        "post_index_rate_v2"=>env('POST_INDEX_RATE_V2' , 'post_index_rate_v2'),//最热贴子v2
        "post_index_non_rate"=>'post_index_non_rate',//非最热贴子
        "post_preheat_propaganda"=>'post_preheat_propaganda',//预热贴子
        "post_vote_data"=>'post_vote_data_', //帖子投票
    ],
    'topic'=>[
        'topic_post_count'=>'topic_post_count',
        'topic_index_new'=>'topic_index_new',
    ]
];