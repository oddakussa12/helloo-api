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
//    $api->post('user/phone/code/signIn' , 'AuthController@handleSignIn')->name('user.phone.sign.in');
    $api->group(['middleware'=>['repeatedSubmit']] , function($api){
//        $api->post('user/phone/signUp' , 'AuthController@phoneSignUp')->name('user.phone.sign.up');
        $api->post('user/signUp' , 'AuthController@signUp')->name('user.sign.up');
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
//        $api->group(['middleware'=>['repeatedSubmit']] , function($api){
//            $api->get('user/im/random' , 'UserController@randRyOnlineUser')->name('user.ry.online.random');
//            $api->get('user/voice/random' , 'UserController@randomVoice')->name('user.voice.random');
//            $api->get('user/video/random' , 'UserController@randomVideo')->name('user.video.random');
//            $api->get('user/video/randomV2' , 'UserController@randomVideoV2')->name('user.video.random.v2');
//            $api->get('user/voice/randomV2' , 'UserController@randomVoiceV2')->name('user.voice.random.v2');
//            $api->get('user/{user}/ryStatus' , 'UserController@isRyOnline')->name('user.ry.online.status');
//        });
//        $api->delete('user/voice/random' , 'UserController@removeVoice')->name('user.voice.random.delete');
//        $api->delete('user/video/random' , 'UserController@removeVideo')->name('user.video.random.delete');


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
        $api->get('user/{user}/friend' , 'UserFriendController@index')->name('user.friend.list');//获取用户朋友列表
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
//            $api->get('tag' , 'TagController@index')->name('tag.index');
//            $api->post('tag' , 'TagController@store')->name('tag.store');
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
//        $api->get('user/ry/planet' , 'UserController@planet')->name('user.ry.online.planet');

        $api->post('user/verify/myself' , 'AuthController@verifyAuthPassword')->name('myself.verify');

//        $api->post('user/{user}/block', 'UserController@block')->name('user.block');
//
//        $api->post('user/{user}/unblock', 'UserController@unblock')->name('user.unblock');

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



//        $api->post('user/game/tag' , 'UserController@gameTag')->name('user.game.tag.store');

        $api->get('user/friend/game/{game}/rank' , 'UserFriendController@gameRank')->where('game', 'coronation|superZero|trumpAdventures')->name('user.friend.game.rank');

        $api->get('game/{game}/event' , 'EventController@event')->where('game', 'coronation|superZero|trumpAdventures')->name('game.event');

        $api->get('set/school' , 'SetController@school')->name('set.school');

        $api->get('user/recommendation' , 'UserController@recommendation')->name('user.recommendation');

        $api->get('user/shop' , 'UserController@shop')->name('user.shop');

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
        $api->patch('group/member' , 'GroupMemberController@update')->name('group.member.update');
        $api->patch('group/{group}' , 'GroupController@update')->name('group.update');
        $api->delete('group/{group}' , 'GroupController@destroy')->name('group.destroy');
        $api->get('group/member' , 'GroupMemberController@index')->name('group.member.index');
        $api->get('group/{group}' , 'GroupController@show')->name('group.show');
        $api->post('group/member' , 'GroupMemberController@join')->name('group.member.join');
        /*****群 结束*****/

        /*****business start*****/
        $api->get('agora/rtc/token' , 'UserController@agoraToken')->name('user.agora.token');


        // $api->get('goods/comment/reply' , 'Business\GoodsCommentsController@reply')->name('goods.comment.reply');


        $api->group(['namespace'=>'Business'] , function($api){

            $api->get('business/notification/activities' , 'NotificationController@activities')->name('notification.activities');
            $api->get('business/search' , 'BusinessController@search')->name('business.search');
            $api->post('business/delivery_cost' , 'BusinessController@deliveryCost')->name('business.delivery_cost');
            $api->get('goods/uncategorized' , 'GoodsController@uncategorized')->name('goods.uncategorized');
            $api->get('goods/recommendation' , 'GoodsController@recommendation')->name('goods.recommendation');

            $api->get('goods/{goods}/like' , 'GoodsController@like')->name('goods.like.index');
            $api->get('shopping_cart' , 'ShoppingCartController@index')->name('business.shopping.cart.index');
            $api->post('order/preview' , 'OrderController@preview')->name('business.order.preview');
            $api->post('order/special' , 'OrderController@specialOrder')->name('business.order.special');
            $api->get('order/myself' , 'OrderController@my')->name('business.order.my');
            $api->get('order/{order}' , 'OrderController@show')->name('business.order.show');

            $api->group(['middleware'=>['repeatedSubmit']] , function($api){
                $api->post('goods/comment' , 'GoodsCommentsController@store')->name('goods.comment.store');
                $api->post('goods/{goods}/like' , 'GoodsController@storeLike')->name('goods.like.store');
                $api->delete('goods/{goods}/like' , 'GoodsController@destroyLike')->name('goods.like.destroy');
                $api->post('goods' , 'GoodsController@store')->name('goods.store');
                $api->put('goods/{goods}' , 'GoodsController@update')->name('goods.update');
                $api->post('delivery/order' , 'DeliveryOrderController@store')->name('goods.delivery.order.store');
                $api->post('shopping_cart' , 'ShoppingCartController@store')->name('business.shopping.cart.store');
                $api->delete('shopping_cart' , 'ShoppingCartController@destroy')->name('business.shopping.cart.destroy');
                $api->post('order/special' , 'OrderController@specialOrder')->name('business.order.special');
                $api->post('order' , 'OrderController@store')->name('business.order.store');
                $api->post('follow' , 'FollowController@store')->name('business.follow.store');
                $api->delete('follow/{follow}' , 'FollowController@destroy')->name('business.follow.destroy');
                $api->post('goods_category' , 'GoodsCategoryController@store')->name('business.goods.category.store');
                $api->patch('goods_category/{goods_category}' , 'GoodsCategoryController@update')->name('business.goods.category.update');
                $api->put('goods_category/sort' , 'GoodsCategoryController@sort')->name('business.goods.category.sort');
                $api->delete('goods_category/{goods_category}' , 'GoodsCategoryController@destroy')->name('business.goods.category.destroy');
            });

            $api->get('goods_category' , 'GoodsCategoryController@index')->name('business.goods.category.index');

            $api->get('follow/myself' , 'FollowController@my')->name('business.follow.my');

            $api->get('follow/{user}' , 'FollowController@index')->name('business.follow.index');

            $api->get('recipient', 'RecipientController@index')->name('recipient.index');
            $api->post('recipient', 'RecipientController@store')->name('recipient.store');
            $api->patch('recipient/{recipient}', 'RecipientController@update')->name('recipient.update');
            $api->delete('recipient/{recipient}', 'RecipientController@destroy')->name('recipient.destroy');
        });

        $api->group(['prefix'=>'dashboard' , 'namespace'=>'Dashboard'] , function ($api) {
            $api->get('order' , 'IndexController@order')->name('dashboard.order');
            $api->get('statistics' , 'IndexController@statistics')->name('dashboard.statistics');
            $api->get('draw' , 'IndexController@draw')->name('dashboard.draw');
        });


        /*****business end*****/


    });
    $api->group(['namespace'=>'Business'] , function($api){
        $api->get('shop_tag' , 'ShopTagController@index')->name('business.shop.tag');

        $api->get('goods/special' , 'GoodsController@special')->name('goods.special');

        $api->get('goods' , 'GoodsController@index')->name('goods.index');

        $api->get('business/special_goods' , 'BusinessController@specialGoods')->name('business.special_goods');

        $api->get('business/discovery/home' , 'BusinessController@home')->name('business.discovery.home');

        $api->get('business/discovery/index' , 'BusinessController@discoveryIndex')->name('business.discovery.index');
        
        $api->get('business/discovery/index_untested' , 'BusinessController@discoveryIndexUntested')->name('business.discovery.indexUntested');

        $api->get('business/settings/view' , 'BusinessController@settings')->name('business.settings');

        $api->get('business/discovery' , 'BusinessController@discovery')->name('business.discovery');

        $api->get('goods/comment' , 'GoodsCommentsController@index')->name('goods.comment.index');

        $api->get('business/search_v2' , 'BusinessController@searchV2')->name('business.search_v2');

        $api->get('business/search' , 'BusinessController@search')->name('business.search');

        $api->get('goods/{goods}' , 'GoodsController@show')->name('goods.show');

        $api->get('promo_code/{promo_code}' , 'PromoCodeController@show')->name('promo.show');

        $api->patch('promo_code/{promo_code}' , 'PromoCodeController@update')->name('promo.update');

        $api->post('business/bitrix_order_callback' , 'BusinessController@bitrixOrderCallback')->name('business.bitrix.order.callback');

        $api->get('business/ship_day_callback' , 'BusinessController@shipDayCallback')->name('business.shipDayCallback.get');

        $api->post('business/ship_day_callback' , 'BusinessController@shipDayCallback')->name('business.shipDayCallback.post');

        // tmp
        $api->get('business/discovery/fixlat' , 'BusinessController@fixShopsLatitudes')->name('business.discovery.fixlat');

    });


    $api->get('sticker/index' , 'StickerController@index')->name('sticker.index');

//    $api->get('user/{user}/tag' , 'UserController@tag')->name('user.tag');

    $api->post('user/contacts' , 'UserController@contacts')->name('user.contacts');

    $api->post('user/contactsV2' , 'UserController@contactsV2')->name('user.contactsV2');

//    $api->post('user/status' , 'UserController@status')->name('user.status');

//    $api->resource('user' , 'UserController' , ['only' => ['show']]);

    $api->get('user' , 'UserController@index')->name('user.index');

    $api->get('aws/{type}/form' , 'AwsController@form')->name('aws.form');

    $api->group(['middleware'=>['guestRefresh']] , function($api){
        $api->post('feedback/network' , 'FeedbackController@network')->name('feedback.network'); //汇报网络状态
        $api->resource('feedback' , 'FeedbackController' , ['only' => ['store']]); //feedback
    });
    $api->post('statistics/download' , 'StatisticsController@download')->name('statistics.download');

    $api->get('user/{user}/type/{type}' , 'AuthController@accountVerification')->where('type', 'phone|nick_name')->name('user.account.verification');

    $api->get('user/account/verification' , 'AuthController@accountNameVerification')->name('user.account.name.verification');

    $api->post('ry/chat' , 'RyChatController@store')->name('user.ry.message.store');

    $api->get('set/common' , 'SetController@commonSwitch')->name('set.common.switch');

    $api->get('event/current' , 'EventController@current')->name('event.current');

    $api->post('event' , 'EventController@store')->name('event.store');

    $api->put('event/{event}' , 'EventController@update')->name('event.update');

    $api->post('statistics/log' , 'StatisticsController@log')->name('statistics.log');

    $api->post('statistics/record/log' , 'StatisticsController@recordLog')->name('statistics.record.log');

    $api->get('app/index' , 'AppController@index')->name('app.index');

    $api->get('app/home' , 'AppController@home')->name('app.home');

    $api->post('app/referrer' , 'AppController@referrer')->name('app.referrer');

    $api->get('school/index' , 'SchoolController@index')->name('school.index');

    $api->post('ry/push' , 'RySetController@push')->name('ry.push');

    $api->group(['prefix'=>'backstage'] , function ($api) {

        $api->patch('special_goods/image' , 'BackStageController@updateSpecialGoodsImage')->name('backstage.update.special_goods.image');

        $api->patch('goods_discounted/switch' , 'BackStageController@updateGoodsDiscountedSwitch')->name('backstage.goods.discounted.switch');

        $api->patch('shop_tag/refresh' , 'BackStageController@refreshShopTag')->name('backstage.refresh.shop_tag');

        $api->patch('version/upgrade' , 'BackStageController@versionUpgrade')->name('backstage.version.upgrade');

        $api->post('block/device' , 'BackStageController@blockDevice')->name('backstage.block.device');

        $api->post('block/user' , 'BackStageController@blockUser')->name('backstage.block.user');

        $api->get('last/online' , 'BackStageController@lastOnline')->name('backstage.last.online');

        $api->get('score' , 'BackStageController@score')->name('backstage.score');

        $api->post('score' , 'BackStageController@storeScore')->name('backstage.store.score');

        $api->patch('shop/{shop}' , 'BackStageController@updateShop')->name('backStage.shop.update');

        $api->post('review/comment' , 'BackStageController@reviewComment')->name('backStage.review.comment');

        $api->post('reject/comment' , 'BackStageController@rejectComment')->name('backStage.reject.comment');

        $api->patch('special_goods' , 'BackStageController@updateSpecialGoods')->name('backStage.special_goods.update');

        $api->patch('delay_special_goods' , 'BackStageController@updateDelaySpecialGoods')->name('backStage.delay_special_goods.update');
    });



    /** 商户 免登陆可访问的接口 start */

    $api->get('user/{user}' , 'UserController@show')->name('user.show');
    $api->get('goods/comment/reply' , 'Business\GoodsCommentsController@reply')->name('goods.comment.reply'); // 二级评论

    /** 商户 免登陆可访问的接口 end */

});



