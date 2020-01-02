<?php
return [
	'qnUploadDomain'=>[
		'video_domain' => 'https://qnidvideo.mmantou.cn/',
		'subtitle_domain' => 'https://qnidsubtitle.mmantou.cn/',
		'thumbnail_domain' => 'https://qnidimage.mmantou.cn/',
		'avatar_domain' => 'https://qnidwebother.mmantou.cn/',
		'cover_domain' => 'https://pv4w34f8r.bkt.clouddn.com/',
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
    'more_than_post_comment_num'=>env('MORE_THAN_POST_COMMENT_NUM', 5),
    'post_throttle_num' =>env('POST_THROTTLE_NUM', 1),
    'post_comment_throttle_num' =>env('POST_COMMENT_THROTTLE_NUM', 1),
];