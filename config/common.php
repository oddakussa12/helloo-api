<?php
return [
    'online_domain'=>[
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

    'forget_password_throttle_num' =>env('FORGET_PASSWORD_THROTTLE_NUM', 1),
    'forget_password_throttle_expired' =>env('FORGET_PASSWORD_THROTTLE_EXPIRED', 1),
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
];