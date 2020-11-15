<?php
return [
    'official_user_id'=>'official_user_id',
    'cron_switch'=>env('CRON_SWITCH' , false),
    'online_domain'=>[
        'dev.api.yooul.net',
        'elb.api.yooul.net',
        'api.yooul.net',
        'api.mmantou.cn',
    ],
	'qnUploadDomain'=>[
//		'video_domain' => 'https://qnidvideo.mmantou.cn/',
//		'subtitle_domain' => 'https://qnidsubtitle.mmantou.cn/',
//		'thumbnail_domain' => 'https://qnidimage.mmantou.cn/',
//		'avatar_domain' => 'https://qnidwebother.mmantou.cn/',
//		'cover_domain' => 'https://pv4w34f8r.bkt.clouddn.com/',
        'video_domain' => 'https://qnidyooulvideo.mmantou.cn/',
        'subtitle_domain' => 'https://qnidyooulsubtitle.mmantou.cn/',
        'thumbnail_domain' => 'https://qnidyooulimage.mmantou.cn/',
        'avatar_domain' => 'https://qnwebothersia.mmantou.cn/',
        'cover_domain' => 'https://pv4w34f8r.bkt.clouddn.com/',
	],
    'awsUploadDomain'=>[
        'video_domain' => 'https://media.yooul.com/',
        'video_domain_cn' => 'https://media.mmantou.cn/',
        'thumbnail_domain' => 'https://media.yooul.com/',
    ],
    'front_domain'=> [
        'h5'=>'yooul.com',
        'h5_test'=>'h5.mmantou.cn',
        'web'=>'web.yooul.com',
        'web_test'=>'web.mmantou.cn',
    ],
    'app_dir' => env('APP_DIR', 'api.yooul.net'),
    'score_date'=>env('SCORE_DATE', '2019-11-25 00:00:00'),
    'rate_coefficient'=>env('RATE_COEFFICIENT', 0.7),
    'like_coefficient'=>env('LIKE_COEFFICIENT', 5),
    'user_rank_coefficient'=>env('USER_RANK_COEFFICIENT', 1),
    'user_rank_add_num'=>env('USER_RANK_ADD_NUM', 1),
    'more_than_post_comment_num'=>env('MORE_THAN_POST_COMMENT_NUM', 5),
    'like_weight'=>env('LIKE_WEIGHT', 0),
    'comment_weight'=>env('COMMENT_WEIGHT', 1),
    'commenter_weight'=>env('COMMENTER_WEIGHT', 0),
    'post_country_weight'=>env('POST_COUNTRY_WEIGHT', 0),

    'like_weight_v2'=>env('LIKE_WEIGHT_V2', 0.2),
    'comment_weight_v2'=>env('COMMENT_WEIGHT_V2', 0.8),
    'commenter_weight_v2'=>env('COMMENTER_WEIGHT_V2', 0.5),
    'post_country_weight_v2'=>env('POST_COUNTRY_WEIGHT_V2', 0.5),

    'sign_up_throttle_num' =>env('SIGN_UP_THROTTLE_NUM', 2),
    'sign_up_throttle_expired' =>env('SIGN_UP_THROTTLE_EXPIRED', 60),
    'forget_password_throttle_num' =>env('FORGET_PASSWORD_THROTTLE_NUM', 2),
    'forget_password_throttle_expired' =>env('FORGET_PASSWORD_THROTTLE_EXPIRED', 10),
    'forget_password_phone_throttle_num' =>env('FORGET_PASSWORD_PHONE_THROTTLE_NUM', 2),
    'forget_password_phone_throttle_expired' =>env('FORGET_PASSWORD_PHONE_THROTTLE_EXPIRED', 10),
    'update_phone_throttle_num' =>env('UPDATE_PHONE_THROTTLE_NUM', 2),
    'update_phone_throttle_expired' =>env('UPDATE_PHONE_THROTTLE_EXPIRED', 10),
    'post_throttle_num' =>env('POST_THROTTLE_NUM', 1),
    'post_throttle_expired' =>env('POST_THROTTLE_EXPIRED', 1),
    'post_comment_throttle_num' =>env('POST_COMMENT_THROTTLE_NUM', 2),
    'post_comment_throttle_expired' =>env('POST_COMMENT_THROTTLE_EXPIRED', 1),
    'post_like_throttle_num' =>env('POST_Like_THROTTLE_NUM', 6),
    'post_like_throttle_expired' =>env('POST_Like_THROTTLE_EXPIRED', 1),
    'post_dislike_throttle_num' =>env('POST_DISLike_THROTTLE_NUM', 6),
    'post_dislike_throttle_expired' =>env('POST_DISLike_THROTTLE_EXPIRED', 1),
    'post_comment_like_throttle_num' =>env('POST_COMMENT_Like_THROTTLE_NUM', 6),
    'post_comment_like_throttle_expired' =>env('POST_COMMENT_Like_THROTTLE_EXPIRED', 1),
    'user_update_send_phone_code_throttle_num' =>env('USER_UPDATE_SEND_PHONE_CODE_THROTTLE_NUM', 2),
    'user_update_send_phone_code_throttle_expired' =>env('USER_UPDATE_SEND_PHONE_CODE_THROTTLE_EXPIRED', 10),
    'user_update_send_email_code_throttle_num' =>env('USER_UPDATE_SEND_EMAIL_CODE_THROTTLE_NUM', 2),
    'user_update_send_email_code_throttle_expired' =>env('USER_UPDATE_SEND_EMAIL_CODE_THROTTLE_EXPIRED', 10),
    'notification_throttle_num'=>env('NOTIFICATION_THROTTLE_NUM' , 20),
    'notification_throttle_expired'=>env('NOTIFICATION_THROTTLE_EXPIRED' , 1),
    'user_follow_throttle_num' =>env('USER_FOLLOW_THROTTLE_NUM', 10),
    'user_follow_throttle_expired' =>env('USER_FOLLOW_THROTTLE_EXPIRED', 1),
    'user_unfollow_throttle_num' =>env('USER_UNFOLLOW_THROTTLE_NUM', 30),
    'user_unfollow_throttle_expired' =>env('USER_UNFOLLOW_THROTTLE_EXPIRED', 1),
    'user_like_throttle_num' =>env('USER_LIKE_THROTTLE_NUM', 10),
    'user_like_throttle_expired' =>env('USER_LIKE_THROTTLE_EXPIRED', 1),
    'user_unlike_throttle_num' =>env('USER_UNLIKE_THROTTLE_NUM', 5),
    'user_unlike_throttle_expired' =>env('USER_UNLIKE_THROTTLE_EXPIRED', 1),

    /************/
    'user_sign_in_phone_code_throttle_num' =>env('USER_SIGN_IN_PHONE_CODE_THROTTLE_NUM', 2),
    'user_sign_in_phone_code_throttle_expired' =>env('USER_SIGN_IN_PHONE_CODE_THROTTLE_EXPIRED', 10),
    'user_update_phone_sms_wait_time' =>env('USER_UPDATE_PHONE_SMS_WAIT_TIME', 300),
    'user_sign_in_sms_wait_time' =>env('USER_SIGN_IN_PHONE_SMS_WAIT_TIME', 300),
    'user_reset_pwd_sms_wait_time' =>env('USER_RESET_PWD_SMS_WAIT_TIME', 300),

    /************/


    'authorization'=>[
        'create_post'=>env('AUTHORIZATION_CREATE_POST' , true),
        'create_post_comment'=>env('AUTHORIZATION_CREATE_POST_COMMENT' , true),
    ],
    'ios_secret'=>'LDKEIJLAKXL',
    'android_secret'=>'PEPVLKASDMW',
    'common_secret'=>'EIKANMECJ',
    'post_new_per'=>env('POST_NEW_PER' , 3),
    'post_rate_per'=>env('POST_RATE_PER' , 7),
    'google_application_credentials'=>env('GOOGLE_APPLICATION_CREDENTIALS' , storage_path().'/app/google/application_default_credentials.json'),
    'google_project_id'=>env('GOOGLE_PROJECT_ID', 'speachregins'),
    'google_project_bucket_mame'=>env('GOOGLE_PROJECT_BUCKET_NAME', 'translation_v3_glossary_2020324'),
    'google_glossary_name'=>env('GOOGLE_GLOSSARY_NAME', 'glossary.csv'),
    'google_glossary_id'=>env('GOOGLE_GLOSSARY_id', 'yooul_v3_glossary_20200325'),
    'google_location'=>env('GOOGLE_LOCATION', 'us-central1'),
    'google_translation_version'=>env('GOOGLE_TRANSLATION_VERSION', 'v3'),
    'refer_friend_num'=>env('REFER_FRIEND_NUM', 3),
    'translation_version'=>env('TRANSLATION_VERSION', 'niu'),
    'report_user_num'=>env('REPORT_USER_NUM' , 5),
    'report_post_num'=>env('REPORT_POST_NUM' , 5),
    'report_limit_num'=>env('REPORT_POST_NUM' , 3),
    'email_code_wait_time'=>env('EMAIL_CODE_WAIT_TIME' , 300),
    'phone_code_wait_time'=>env('PHONE_CODE_WAIT_TIME' , 300),
    'user_name_update_time'=>env('USER_NAME_UPDATE_TIME' , 1),
    'emoji_md5'=>env('EMOJI_MD5' , ''),
    'prohibited_content'=>env('PROHIBITED_CONTENT' , ''),
    'prohibited_default_uuid'=>env('PROHIBITED_DEFAULT_UUID' , ''),
];