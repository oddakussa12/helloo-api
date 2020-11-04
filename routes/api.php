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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
$api = app('Dingo\Api\Routing\Router');

$V1Params = [
    'version' => 'v1',
    'prefix' => LaravelLocalization::setLocale().config('api.suffix'),
    'middleware'=>['cors'],
    'namespace' => 'App\\Http\\Controllers\V1',
];

$api->group($V1Params , function ($api){
    $api->group(['middleware'=>['guestRefresh']] , function($api){
        $api->group(['middleware'=>['operationLog' , 'lastActive']] , function ($api){
            $api->resource('post', 'PostController', ['only' => ['index']]);
            /*****热门话题 开始*****/
            $api->get('topic/hot', 'TopicController@hot')->name('topic.hot');
            /*****热门话题 结束*****/
        });
        $api->get('post/user/{user}' , 'PostController@showPostByUser')->name('show.post.by.user');

        $api->get('post/top' , 'PostController@top')->name('post.top');
        $api->get('post/banner' , 'PostController@banner')->name('post.banner');
        $api->get('post/carousel' , 'PostController@carousel')->name('post.carousel');
        $api->get('post/fine' , 'PostController@fine')->name('post.fine');
        $api->get('post/hot' , 'PostController@hot')->name('post.hot');

        $api->get('postComment/more/{commentTopId}' , 'PostCommentController@moreComment')->name('show.more.comment');
        $api->get('postComment/locate/{commentId}' , 'PostCommentController@locateComment')->name('show.locate.comment');
        $api->get('postComment/post/{uuid}' , 'PostCommentController@showByPostUuid')->name('show.comment.by.post');



        /*****话题下贴子 开始*****/
        $api->get('topic/{topic}/post', 'TopicController@post')->name('topic.post');
        $api->get('topic/post', 'TopicController@topicPost')->name('topic.post.v2');
        /*****话题下贴子 结束*****/


    });
    $api->group(['middleware'=>'redisThrottle:'.config('common.forget_password_throttle_num').','.config('common.forget_password_throttle_expired')] , function ($api){
        $api->post('user/forgetPwd' , 'AuthController@forgetPwd')->name('user.forget.pwd');
    });

    $api->post('user/resetPwd' , 'AuthController@resetPwd')->name('user.reset.pwd');
    $api->post('user/phone/resetPwd' , 'AuthController@resetPwdByPhone')->name('user.phone.reset.pwd');

    $api->group(['middleware'=>'redisThrottle:'.config('common.sign_up_throttle_num').','.config('common.sign_up_throttle_expired') , 'repeatedSubmit'] , function ($api){
        $api->post('user/signUp' , 'AuthController@signUp')->name('sign.up');
        $api->post('user/phone/signUp' , 'AuthController@handleSignUp')->name('user.phone.sign.up');
    });
    //游客模式生成用户
//    $api->post('user/guestSignUp' , 'AuthController@guestSignUp')->name('guest.signin');
    $api->post('user/signIn' , 'AuthController@signIn')->name('sign.in');
    $api->post('user/phone/signIn' , 'AuthController@handleSignIn')->name('user.phone.sign.in');
    $api->get('user/signOut' , 'AuthController@signOut')->name('sign.out');
//    $api->get('auth/smsCode' , 'AuthController@smsSend')->name('auth.sms.send');


    $api->group(['middleware'=>['refresh']] , function($api){

        /*****我关注的话题 开始*****/
        $api->get('topic/myFollow', 'TopicController@myFollow')->name('my.follow.topic');
        /*****我关注的话题 结束*****/

        /*****关注话题 开始*****/
        $api->put('topic/{topic}/follow', 'TopicController@follow')->name('follow.topic');
        /*****关注话题 结束*****/

        /*****取消关注话题 开始*****/
        $api->put('topic/{topic}/unFollow', 'TopicController@unFollow')->name('unfollow.topic');
        /*****取消关注话题 结束*****/

        /*****报告 开始*****/
        $api->resource('report', 'ReportController',['only' => ['store']]);
        /*****报告 结束*****/

        /*****好友 开始*****/
        $api->get('my/friend' , 'UserFriendController@my')->name('my.friend');//我的好友
        $api->post('my/friend' , 'UserFriendController@update')->name('my.friend.update');//好友备注
        $api->delete('my/friend/{friend}' , 'UserFriendController@destroy')->name('my.friend.destroy');//删除我的好友
        /*****好友 结束*****/

        /*****好友请求 开始*****/
        $api->group(['middleware'=>['blacklist' , 'repeatedSubmit']] , function ($api){
            $api->post('friend/request' , 'UserFriendRequestController@store')->name('user.friend.request.store');//发起好友请求
            $api->patch('friend/{friend}/accept' , 'UserFriendRequestController@accept')->name('user.friend.request.accept');//好友请求响应接受
            $api->patch('friend/{friend}/refuse' , 'UserFriendRequestController@refuse')->name('user.friend.request.refuse');//好友请求响应拒绝
        });
        $api->get('my/friend/request' , 'UserFriendRequestController@my')->name('my.friend.request');//我的好友请求
        /*****好友请求 结束*****/


        /*****特殊好友关系请求 开始*****/
        $api->group(['middleware'=>['blacklist' , 'repeatedSubmit']] , function ($api){
            $api->post('affinity/request' , 'UserFriendAffinityController@store')->name('user.affinity.request.store');//发起好友请求
            $api->get('affinity/{friend}/top', 'UserFriendAffinityController@top')->name('user.affinity.top');//好友请求列表
            $api->patch('affinity/{friend}/accept' , 'UserFriendAffinityController@accept')->name('user.affinity.request.accept');//好友请求响应接受
            $api->patch('affinity/{friend}/refuse' , 'UserFriendAffinityController@refuse')->name('user.affinity.request.refuse');//好友请求响应拒绝
        });

        $api->get('affinity/{friend}/heart', 'UserFriendAffinityController@heart')->name('user.affinity.heart'); //获取心的数量
        $api->get('affinity/{user}/{friend}/sign', 'UserFriendAffinityController@getSignInList')->name('user.affinity.sign.list');//签到列表
        $api->get('affinity/{friend}/main', 'UserFriendAffinityController@main')->name('user.affinity.main');//好友关系主页
        $api->get('topic/{friend}/top', 'TopicController@FriendTop')->name('user.topic.top');//好友关系主页


        /*****特殊好友关系请求 结束*****/

        /*****评论 开始*****/
        $api->get('postComment/myself' , 'PostCommentController@myself')->name('comment.myself');//我的评论
        $api->get('postComment/like' , 'PostCommentController@mylike')->name('comment.mylike');//我的点赞的评论
        /*****评论 结束*****/

        $api->get('user/profile' , 'AuthController@me')->name('my.profile');
        $api->get('user/{user}/view' , 'UserController@viewPage')->name('my.view'); // 我的主页 浏览量 详细页
        $api->get('post/myself' , 'PostController@myself')->name('post.myself');
        $api->group(['middleware'=>['operationLog' , 'lastActive']] , function ($api){
            $api->get('message/token' , 'PrivateMessageController@token')->name('message.token');
            $api->group(['middleware'=>['repeatedSubmit']] , function ($api){
                $api->post('user/update/myself' , 'AuthController@update')->name('myself.update');
            });
            $api->get('user/ry/planet' , 'UserController@planet')->name('user.ry.online.planet');
            $api->get('user/ry/filter' , 'UserController@filter')->name('user.ry.online.filter');
            $api->post('user/update/myself/auth' , 'AuthController@updateAuth')->name('myself.update.auth');
            $api->post('user/update/myself/name' , 'AuthController@updateUserName')->name('myself.update.name');
            $api->post('user/update/myself/phone' , 'AuthController@updateUserPhone')->name('myself.update.phone');
            $api->post('user/update/myself/email' , 'AuthController@updateUserEmail')->name('myself.update.email');
            $api->group(['middleware'=>['redisThrottle:'.config('common.user_update_send_phone_code_throttle_num').','.config('common.user_update_send_phone_code_throttle_expired') , 'blacklist']] , function ($api){
                $api->post('user/update/phone/code' , 'AuthController@sendUpdatePhoneCode')->name('myself.update.send.phone.code');
            });
            $api->group(['middleware'=>['redisThrottle:'.config('common.user_update_send_email_code_throttle_num').','.config('common.user_update_send_email_code_throttle_expired') , 'blacklist']] , function ($api){
                $api->post('user/update/email/code' , 'AuthController@sendUpdateEmailCode')->name('myself.update.send.email.code');
            });
        });

        $api->post('user/verify/myself' , 'AuthController@verifyAuthPassword')->name('myself.verify');
        $api->get('user/getqntoken' , 'UserController@getQiniuUploadToken')->name('qn.token');
        $api->get('user/myfollowrandtwo' , 'UserController@myFollowRandTwo')->name('follow.two');


        // 搜索功能-ES
        $api->get('search', 'SearchController@index')->name('aggregation.search');
        // 热门搜索
        $api->get('hot/search', 'SearchController@hotSearch')->name('search.hot');

        $api->post('user/{user}/block', 'UserController@block')->name('user.block');
        $api->post('user/{user}/unblock', 'UserController@unblock')->name('user.unblock');
        $api->post('post/{uuid}/block', 'PostController@block')->name('post.block');

//        $api->put('post/{uuid}/favorite' , 'PostController@favorite')->name('post.favorite');
//        $api->put('post/{uuid}/unfavorite' , 'PostController@unfavorite')->name('post.unFavorite');

        $api->group(['middleware'=>['redisThrottle:'.config('common.post_like_throttle_num').','.config('common.post_like_throttle_expired') , 'blacklist']] , function ($api){
            $api->put('post/{uuid}/like' , 'PostController@like')->name('post.like');
        });

        $api->group(['middleware'=>['redisThrottle:'.config('common.post_dislike_throttle_num').','.config('common.post_dislike_throttle_expired') , 'blacklist']] , function ($api){
            $api->put('post/{uuid}/dislike' , 'PostController@dislike')->name('post.dislike');
        });

        $api->put('post/{uuid}/revokeLike' , 'PostController@revokeLike')->name('post.revokeLike');
        $api->put('post/{uuid}/revokeDislike' , 'PostController@revokeDislike')->name('post.revokeDislike');
        $api->group(['middleware'=>['redisThrottle:'.config('common.post_comment_like_throttle_num').','.config('common.post_comment_like_throttle_expired') , 'blacklist']] , function ($api) {
            $api->put('postComment/{comment_id}/like', 'PostCommentController@like')->name('comment.like');
        });
//                $api->put('postComment/{comment_id}/dislike' , 'PostCommentController@dislike');
        $api->put('postComment/{comment_id}/revokeVote' , 'PostCommentController@revokeVote')->name('comment.revokeVote');
//        $api->put('postComment/{comment_id}/favorite' , 'PostCommentController@favorite')->name('comment.favorite');
//        $api->put('postComment/{comment_id}/unfavorite' , 'PostCommentController@unfavorite')->name('comment.unFavorite');
        $api->get('user/myfollow' , 'UserController@myFollow')->name('myself.follow');
        $api->get('user/followme' , 'UserController@followMe')->name('myself.followMe');

        $api->group(['middleware'=>['blacklist']] , function ($api){
            $api->group(['middleware'=>['redisThrottle:'.config('common.user_follow_throttle_num').','.config('common.user_follow_throttle_expired')]] , function ($api){
                $api->put('user/{id}/follow' , 'UserController@follow')->name('user.follow');
            });
            $api->group(['middleware'=>['redisThrottle:'.config('common.user_unfollow_throttle_num').','.config('common.user_unfollow_throttle_expired')]] , function ($api){
                $api->put('user/{id}/unfollow' , 'UserController@unfollow')->name('user.unFollow');
            });
            $api->group(['middleware'=>['redisThrottle:'.config('common.user_like_throttle_num').','.config('common.user_like_throttle_expired')]] , function ($api){
                $api->put('user/{user}/like' , 'UserController@profileLike')->name('user.profile.like');
            });
            $api->group(['middleware'=>['redisThrottle:'.config('common.user_unlike_throttle_num').','.config('common.user_unlike_throttle_expired')]] , function ($api){
                $api->put('user/{user}/revokeLike' , 'UserController@profileRevokeLike')->name('user.profile.revoke.like');
            });
            $api->group(['middleware'=>['redisThrottle:'.config('common.post_throttle_num').','.config('common.post_throttle_expired')]] , function ($api){
                $api->post('post', 'PostController@store')->name('post.store'); // 发帖
                $api->post('vote', 'PostVoteController@store')->name('post.vote.store');// 发布投票贴
                $api->put('vote', 'PostVoteController@vote')->name('post.vote.status'); // 选一个选项投票
            });
            $api->group(['middleware'=>['redisThrottle:'.config('common.post_comment_throttle_num').','.config('common.post_comment_throttle_expired')]] , function ($api){
                $api->post('postComment' , 'PostCommentController@store')->name('comment.store');
            });
        });

        $api->put('vote', 'PostVoteController@vote')->name('post.vote.status'); // 选一个选项投票

        //其他人的关注&粉丝列表
        $api->get('user/{id}/myfollow' , 'UserController@otherMyFollow')->name('other.follow');
        $api->get('user/{id}/followme' , 'UserController@otherFollowMe')->name('other.followMe');
        $api->get('user/emoji', 'UserController@getEmoji')->name('user.getEmoji');
        $api->post('user/emoji', 'UserController@setEmoji')->name('user.setEmoji');
        $api->put('user/emoji', 'UserController@updateEmoji')->name('user.updateEmoji');

        $api->delete('post/{uuid}' , 'PostController@destroy')->name('post.delete');

        $api->resource('postComment' , 'PostCommentController' , ['only' => ['destroy']]);
        $api->resource('position' , 'PositionController', ['only' => ['store']]); //用户地址位置

        $api->group(['middleware'=>['operationLog' , 'lastActive' , 'redisThrottle:'.config('common.notification_throttle_num').','.config('common.notification_throttle_expired')]] , function ($api){
            $api->get('notification/count' , 'NotificationController@count')->name('notice.count');
            $api->get('notification/unreadCount' , 'NotificationController@unreadCount')->name('notice.unreadCount');
        });
        $api->put('notification/readAll' , 'NotificationController@readAll')->name('notice.readAll');
        $api->put('notification/{id}' , 'NotificationController@read')->name('notice.read');
        $api->get('notification/home' , 'NotificationController@home')->name('notification.home');
        $api->get('notification/{id}' , 'NotificationController@detail')->name('notice.detail');

        $api->post('device/update', 'DeviceController@update')->name('device.update');

        $api->put('app/mode/{mode}' , 'AppController@mode')->where('model', 'out|in')->name('app.mode');

    });
    $api->group(['middleware'=>['guestRefresh']] , function($api){
        $api->get('affinity/rule', 'UserFriendAffinityController@rule')->name('user.affinity.rule');//等级规则

        $api->get('user/userranking' , 'UserController@rank')->name('user.rank');

        $api->get('postComment/user/{user}' , 'PostCommentController@showPostCommentByUser')->name('show.comment.by.user');

        $api->get('postComment/like/{user}' , 'PostCommentController@showPostCommentLikeByUser')->name('show.like.comment.by.user');
        $api->resource('feedback' , 'FeedbackController' , ['only' => ['store']]);
        $api->group(['middleware'=>['operationLog' , 'lastActive']] , function ($api){
            $api->get('post/{uuid}' , 'PostController@showByUuid')->name('post.show');
        });
        $api->get('notification' , 'NotificationController@index')->name('notification.index');
        $api->resource('tag' , 'TagController' , ['only' => ['index' , 'store']]);
        $api->get('tag/hot' , 'TagController@hot')->name('tag.hot');
        $api->get('event' , 'EventController@index')->name('event.index');
        $api->group(['middleware'=>['operationLog' , 'lastActive']] , function ($api){
            $api->resource('user' , 'UserController' , ['only' => ['show']]);
        });
    });
    $api->post('message/translate' , 'PrivateMessageController@translate')->name('private.message.translate');


    $api->resource('device', 'DeviceController', ['only' => ['store']]);
    $api->get('device', 'DeviceController@index');

//    $api->get('user/{user}/friend' , 'UserFriendController@index')->name('user.friend');
    $api->get('user/{user}/type/{type}' , 'AuthController@accountExists')->where('type', 'email|name|phone|nick_name')->name('user.account.exists');
    $api->get('user' , 'UserController@index')->name('user.name.search');
    $api->get('user/name/{name}/email/{email}/cancelled' , 'UserController@cancelled')->name('user.account.cancelled');
    $api->get('app/clear/cache' , 'AppController@clearCache')->name('app.clear.cache');
    $api->get('app/version' , 'AppController@index')->name('app.index');
//    $api->get('rong/state/user/{id}' , 'RySetController@userCheckOnline')->name('ry.user.is_online');
    $api->get('set/post/rate' , 'SetController@postRate')->name('set.post.rate');
    $api->get('set/dx/switch' , 'SetController@dxSwitch')->name('set.dx.switch');
    $api->post('set/dx/clearDxCache' , 'SetController@clearDxCache')->name('set.dx.switch.clear.cache');
    $api->get('post/{uuid}/country' , 'PostController@country')->name('post.country');
    $api->get('user/{id}/ryStatus' , 'UserController@isRyOnline')->name('user.ry.online.status');
    $api->post('user/ry/online' , 'UserController@updateRyUserOnlineState')->name('user.ry.online.status.set');
    $api->get('user/ry/random' , 'UserController@randRyOnlineUser')->name('user.ry.online.random');
    $api->get('user/ry/refer' , 'UserController@referFriend')->name('user.ry.online.refer');
    $api->get('ry/user' , 'RyChatController@user')->name('user.ry.user');
    $api->get('ry/chat' , 'RyChatController@index')->name('user.ry.message.index');
    $api->post('ry/chat' , 'RyChatController@store')->name('user.ry.message.store');
    $api->get('ry/room/chat/translation' , 'RyChatController@roomChatTranslation')->name('user.ry.room.message.translation');
    $api->get('ry/room/chat' , 'RyChatController@showByRoom')->name('user.ry.room.message.index');
    $api->post('ry/room/chat' , 'RyChatController@storeRoomChat')->name('user.ry.room.message.store');
    $api->group(['middleware'=>['backAuth']] , function($api){
        $api->post('ry/set/block' , 'RySetController@blockUser')->name('user.ry.set.block');
        $api->post('ry/set/unblock' , 'RySetController@unblockUser')->name('user.ry.set.unblock');
        $api->get('bk/essence/post' , 'BackStageController@getCustomEssencePost')->name('bk.post.essence.post');
        $api->patch('bk/essence/post/{post}' , 'BackStageController@setCustomEssencePost')->name('bk.post.essence.update');
        $api->patch('bk/carousel/post/{post}' , 'BackStageController@setCarousel')->name('bk.post.carousel.update');
        $api->patch('bk/service/message' , 'BackStageController@setServiceMessage')->name('bk.service.message.update');
        $api->patch('bk/banner' , 'BackStageController@setBanner')->name('bk.banner.update');
        $api->patch('bk/event' , 'BackStageController@setEvent')->name('bk.event.update');
        $api->delete('bk/postComment/{postComment}' , 'BackStageController@destroyComment')->name('bk.postComment.delete');
        $api->delete('bk/post/{post}' , 'BackStageController@destroyPost')->name('bk.post.delete');
        $api->patch('bk/user/{user}/follow' , 'BackStageController@setFollowUser')->name('bk.user.follow');
        $api->patch('bk/non_fine/{post}/post' , 'BackStageController@setNonFinePost')->name('bk.post.non_fine.update');
        $api->post('bk/set/topic/hot' , 'BackStageController@setHotTopic')->name('bk.set.topic.hot');
        $api->get('bk/get/topic/hot' , 'BackStageController@getHotTopic')->name('bk.get.topic.hot');
        $api->post('bk/get/topic/hot' , 'BackStageController@getHotTopic')->name('bk.get.topic.hot');
        $api->post('bk/set/search/hot' , 'BackStageController@setHotSearch')->name('bk.set.search.hot');
        $api->post('bk/set/index/switch' , 'BackStageController@setIndexSwitch')->name('bk.set.index.switch');
        $api->post('bk/set/post/gravity' , 'BackStageController@setPostGravity')->name('bk.set.post.gravity');
        $api->post('bk/set/post/rate' , 'BackStageController@setPostRate')->name('bk.set.post.rate');
        $api->post('bk/set/user/post/preheat/coefficient' , 'BackStageController@setUserAndPostPreheatCoefficient')->name('bk.set.user.and.post.preheat.coefficient');
        $api->post('bk/set/user/level' , 'BackStageController@setUserLevel')->name('bk.set.user.level');
        $api->post('bk/set/post/preheat' , 'BackStageController@setPostPreheat')->name('bk.set.post.preheat');
        $api->post('bk/set/fake/like/coefficient' , 'BackStageController@setFakeLikeCoefficient')->name('bk.set.fake.like.coefficient');
    });
    $api->group(['middleware'=>['appAuth']] , function($api){
        $api->get('set/common' , 'SetController@commonSwitch')->name('set.common.switch');
    });
    $api->get('translation' , 'TranslationController@index')->name('translation.index');
    $api->get('google/token' , 'GoogleController@token')->name('google.token');
    $api->group(['middleware'=>['repeatedSubmit']] , function ($api){
        $api->post('test/index' , 'TestController@test')->name('test.test');
    });

});



