<?php

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


$api = app('Dingo\Api\Routing\Router');

$V1Params = [
    'version' => 'v1',
    'prefix' => LaravelLocalization::setLocale().config('api.suffix'),
    'middleware'=>['cors'],
    'namespace' => 'App\\Http\\Controllers\V1',
];

$api->group($V1Params , function ($api){

    $api->group(['middleware'=>['repeatedSubmit' , 'redisThrottle:'.config('common.forget_password_phone_throttle_num').','.config('common.forget_password_phone_throttle_num')]] , function ($api){
        $api->post('user/forgetPwd' , 'AuthController@forgetPwdCode')->name('user.forget.pwd');
    });

    $api->post('user/phone/resetPwd' , 'AuthController@resetPwdByPhone')->name('user.phone.reset.pwd');

    $api->post('user/phone/signIn' , 'AuthController@signIn')->name('sign.in');
    $api->post('user/phone/code/signIn' , 'AuthController@handleSignIn')->name('user.phone.sign.in');
    $api->group(['middleware'=>['repeatedSubmit']] , function($api){
        $api->post('user/phone/signUp' , 'AuthController@phoneSignUp')->name('user.phone.sign.up');
    });
    $api->group(['middleware'=>['redisThrottle:'.config('common.user_sign_in_phone_code_throttle_num').','.config('common.user_sign_in_phone_code_throttle_expired')]] , function ($api){
        $api->post('user/phone/code' , 'AuthController@signInPhoneCode')->name('sign.in.phone.code');
    });
//    $api->get('user/signOut' , 'AuthController@signOut')->name('sign.out');

    $api->post('user/ry/online' , 'UserController@updateRyUserOnlineState')->name('user.ry.online.status.set');
    $api->group(['middleware'=>['refresh']] , function($api){
        $api->post('statistics/upload/fail' , 'StatisticsController@uploadFail')->name('upload.fail');
    });
    $api->group(['middleware'=>['refresh' , 'operationLog']] , function($api){
        $api->group(['middleware'=>['repeatedSubmit']] , function($api){
            $api->get('user/im/random' , 'UserController@randRyOnlineUser')->name('user.ry.online.random');
            $api->get('user/voice/random' , 'UserController@randomVoice')->name('user.voice.random');
            $api->get('user/video/random' , 'UserController@randomVideo')->name('user.video.random');
            $api->get('user/video/randomV2' , 'UserController@randomVideoV2')->name('user.video.random.v2');
            $api->get('user/voice/randomV2' , 'UserController@randomVoiceV2')->name('user.voice.random.v2');
            $api->get('user/{user}/ryStatus' , 'UserController@isRyOnline')->name('user.ry.online.status');
        });
        $api->delete('user/voice/random' , 'UserController@removeVoice')->name('user.voice.random.delete');
        $api->delete('user/video/random' , 'UserController@removeVideo')->name('user.video.random.delete');

        /*****答题 开始*****/
        $api->resource('answer', 'AnswerController',['only' => ['store']]);
        /*****答题 结束*****/

        /*****道具 开始*****/
        $api->resource('props', 'PropsController',['only' => ['index']]);
        $api->get('props/bgm', 'PropsController@bgm')->name('props.bgm');
        $api->get('props/recommendation', 'PropsController@recommendation')->name('props.recommendation');
        $api->get('props/category', 'PropsController@category')->name('props.category');
        $api->get('props/{category}/home', 'PropsController@home')->name('props.home');
        /*****道具 结束*****/

        /*****报告 开始*****/
        $api->resource('report', 'ReportController',['only' => ['store']]);
        /*****报告 结束*****/

        /*****好友 开始*****/
        $api->get('my/friend' , 'UserFriendController@my')->name('my.friend');//我的好友
//        $api->post('my/friend' , 'UserFriendController@update')->name('my.friend.update');//好友备注
        $api->delete('my/friend/{friend}' , 'UserFriendController@destroy')->name('my.friend.destroy');//删除我的好友
        $api->get('user/{userId}/friend' , 'UserFriendController@index')->name('friend.list');//获取用户朋友列表
        /*****好友 结束*****/

        /*****好友请求 开始*****/
        $api->group(['middleware'=>['repeatedSubmit']] , function ($api){
            $api->post('friend/request' , 'UserFriendRequestController@store')->name('user.friend.request.store');//发起好友请求
            $api->patch('friend/request/{request}/accept' , 'UserFriendRequestController@accept')->name('user.friend.request.accept');//好友请求响应接受
            $api->patch('friend/request/{request}/refuse' , 'UserFriendRequestController@refuse')->name('user.friend.request.refuse');//好友请求响应拒绝
        });

//        $api->get('my/friend/request' , 'UserFriendRequestController@my')->name('my.friend.request');//我的好友请求
        /*****好友请求 结束*****/


        $api->get('user/profile' , 'AuthController@me')->name('my.profile');

        $api->get('ry/token' , 'RySetController@token')->name('ry.token');
        $api->group(['middleware'=>['repeatedSubmit']] , function ($api){
            $api->get('tag' , 'TagController@index')->name('tag.index');
            $api->post('tag' , 'TagController@store')->name('tag.store');
//            $api->get('user/{user}/tag' , 'UserController@tag')->name('user.tag');
            $api->put('user/myself' , 'AuthController@update')->name('myself.update');
            $api->patch('user/myself' , 'AuthController@fill')->name('myself.fill');
            $api->patch('user/pwd' , 'AuthController@password')->name('myself.update.password');
            $api->patch('user/auth' , 'AuthController@updateAuth')->name('myself.update.auth');
            $api->patch('user/name' , 'AuthController@updateName')->name('myself.update.name');
            $api->group(['middleware'=>['redisThrottle:'.config('common.update_phone_throttle_num').','.config('common.update_phone_throttle_expired')]] , function ($api){
                $api->post('user/new/phone' , 'AuthController@newPhoneCode')->name('myself.update.phone');
            });
            $api->put('user/{user}/like' , 'UserController@like')->name('user.like');

            $api->put('user/username/prompt' , 'AuthController@usernamePrompt')->name('user.username.prompt');

            $api->post('game/score' , 'GameScoreController@store')->name('game.score.store');
        });
        $api->get('user/ry/planet' , 'UserController@planet')->name('user.ry.online.planet');

        $api->post('user/verify/myself' , 'AuthController@verifyAuthPassword')->name('myself.verify');

        $api->post('user/{user}/block', 'UserController@block')->name('user.block');

        $api->post('user/{user}/unblock', 'UserController@unblock')->name('user.unblock');

        $api->put('app/mode/{mode}' , 'AppController@mode')->where('model', 'out|in')->name('app.mode');
        
        $api->post('statistics/duration' , 'StatisticsController@duration')->name('statistics.duration');

        $api->post('statistics/invitation' , 'StatisticsController@invitation')->name('statistics.invitation');

        $api->post('statistics/type/{type}/matchFailed' , 'StatisticsController@matchFailed')->where('type', 'im|voice|video')->name('statistics.matchFailed');

        $api->post('statistics/type/{type}/matchSucceed' , 'StatisticsController@matchSucceed')->where('type', 'im|voice|video')->name('statistics.matchSucceed');

        $api->get('notification/system' , 'NotificationController@system')->name('user.notification.system');

        $api->get('notification/system/last' , 'NotificationController@last')->name('user.notification.last');

        $api->get('aws/identityToken' , 'AwsController@identityToken')->name('aws.identityToken');
        
        $api->get('aws/sts' , 'AwsController@sts')->name('aws.sts');

        $api->get('aws/{object}/preSignedUrl' , 'AwsController@preSignedUrl')->name('aws.preSignedUrl');

        $api->get('game/{game}/rank/day' , 'GameScoreController@day')->where('game', 'coronation|superZero|trumpAdventures')->name('game.score.day');

        $api->get('game/{game}/rank/week' , 'GameScoreController@week')->where('game', 'coronation|superZero|trumpAdventures')->name('game.score.week');



        $api->post('user/game/tag' , 'UserController@gameTag')->name('user.game.tag.store');

        $api->get('user/friend/game/{game}/rank' , 'UserFriendController@gameRank')->where('game', 'coronation|superZero|trumpAdventures')->name('user.friend.game.rank');

        $api->get('game/{game}/event' , 'EventController@event')->where('game', 'coronation|superZero|trumpAdventures')->name('game.event');

        $api->get('set/school' , 'SetController@school')->name('set.school');

        $api->get('user/recommendation' , 'UserController@recommendation')->name('user.recommendation');

        /*****个人中心 开始*****/
        $api->get('user/center/media/{user?}' , 'UserCenterController@media')->name('user.center.media'); // 获取video/photo
        $api->post('user/center/media' , 'UserCenterController@storeMedia')->name('user.center.storeMedia'); // 提交video/photo
        $api->delete('user/center/media/{id}/{type}' , 'UserCenterController@destroyMedia')->name('user.center.destroyMedia'); // 删除video/photo
        $api->get('user/center/privacy' , 'UserCenterController@privacy')->name('user.center.privacy'); // 获取隐私配置
        $api->patch('user/center/privacy' , 'UserCenterController@updatePrivacy')->name('user.center.updatePrivacy'); // 修改隐私配置
        $api->post('user/center/like' , 'UserCenterController@like')->name('user.center.like'); // 点赞video/photo
        $api->get('user/center/score' , 'UserCenterController@totalScore')->name('user.center.totalScore'); // 总积分
        $api->get('user/center/{user}/medal' , 'UserCenterController@medal')->name('user.center.medal'); // 勋章列表
        $api->get('user/center/{num}/top' , 'UserCenterController@top')->name('user.center.top'); // top100
        $api->get('user/friend/{num}/recommend' , 'UserCenterController@recommend')->name('friend.recommend');//获取用户朋友推荐列表

        /*****个人中心 结束*****/

        /*****群 开始*****/
        $api->get('my/group' , 'GroupController@my')->name('group.my');
        $api->post('group' , 'GroupController@store')->name('group.store');
        $api->patch('group/{group}' , 'GroupController@update')->name('group.update');
        $api->delete('group/{group}' , 'GroupController@destroy')->name('group.destroy');
        $api->get('group/member' , 'GroupMemberController@index')->name('group.member.index');
        $api->get('group/{group}' , 'GroupController@show')->name('group.show');
        $api->patch('group/member' , 'GroupMemberController@update')->name('group.member.update');
        $api->post('group/member' , 'GroupMemberController@join')->name('group.member.join');
        /*****群 结束*****/

    });
    $api->get('user/{user}/tag' , 'UserController@tag')->name('user.tag');

    $api->post('user/contacts' , 'UserController@contacts')->name('user.contacts');

    $api->post('user/contactsV2' , 'UserController@contactsV2')->name('user.contactsV2');

    $api->post('user/status' , 'UserController@status')->name('user.status');

    $api->resource('user' , 'UserController' , ['only' => ['show']]);

    $api->get('user' , 'UserController@index')->name('user.index');

    $api->get('aws/{type}/form' , 'AwsController@form')->name('aws.form');

    $api->group(['middleware'=>['guestRefresh']] , function($api){
        $api->post('feedback/network' , 'FeedbackController@network')->name('feedback.network'); //汇报网络状态
        $api->resource('feedback' , 'FeedbackController' , ['only' => ['store']]); //feedback
    });
    $api->post('statistics/download' , 'StatisticsController@download')->name('statistics.download');


    $api->get('user/{user}/type/{type}' , 'AuthController@accountVerification')->where('type', 'phone|nick_name')->name('user.account.verification');

    $api->post('ry/chat' , 'RyChatController@store')->name('user.ry.message.store');

    $api->get('set/common' , 'SetController@commonSwitch')->name('set.common.switch');

    $api->get('event/current' , 'EventController@current')->name('event.current');

    $api->post('event' , 'EventController@store')->name('event.store');

    $api->put('event/{event}' , 'EventController@update')->name('event.update');

    $api->post('statistics/log' , 'StatisticsController@log')->name('statistics.log');

    $api->post('statistics/record/log' , 'StatisticsController@recordLog')->name('statistics.record.log');

    $api->get('app/index' , 'AppController@index')->name('app.index');

    $api->get('app/home' , 'AppController@home')->name('app.home');

    $api->get('school/index' , 'SchoolController@index')->name('school.index');

    $api->post('ry/push' , 'RySetController@push')->name('ry.push');

    $api->get('question/index' , 'QuestionController@index')->name('question.index');

    $api->get('question/hot' , 'QuestionController@hot')->name('question.hot');

    $api->patch('backstage/version/upgrade' , 'BackStageController@versionUpgrade')->name('backStage.version.upgrade');

    $api->get('backstage/last/online' , 'BackStageController@lastOnline')->name('backStage.last.online');
    $api->get('backstage/score' , 'BackStageController@score')->name('backStage.score');
    $api->post('backstage/score' , 'BackStageController@storeScore')->name('backStage.storeScore');

    $api->get('test/redis' , 'TestController@redis')->name('test.redis');
    $api->get('test/push' , 'TestController@push')->name('test.push');
    $api->get('test/broadcast' , 'TestController@broadcast')->name('test.broadcast');
    $api->post('test/token' , 'TestController@token')->name('test.token');
    $api->get('test/es' , 'TestController@es')->name('test.es');
    $api->get('test/test' , 'TestController@test')->name('test.test');

    $api->get('test/aws' , 'TestController@aws')->name('test.aws');
    $api->get('test/index' , 'TestController@index')->name('test.index');
    $api->get('test/send' , 'TestController@send')->name('test.send');
    $api->get('test/fcm' , 'TestController@fcm')->name('test.fcm');
    $api->get('test/office' , 'TestController@office')->name('test.office');
    $api->get('test/ding' , 'TestController@ding')->name('test.ding');
    $api->get('test/sms' , 'TestController@sms')->name('test.sms');
});



