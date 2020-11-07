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

    $api->group(['middleware'=>'redisThrottle:'.config('common.forget_password_throttle_num').','.config('common.forget_password_throttle_expired')] , function ($api){
        $api->post('user/forgetPwd' , 'AuthController@forgetPwd')->name('user.forget.pwd');
    });

    $api->post('user/resetPwd' , 'AuthController@resetPwd')->name('user.reset.pwd');
    $api->post('user/phone/resetPwd' , 'AuthController@resetPwdByPhone')->name('user.phone.reset.pwd');

    $api->group(['middleware'=>'redisThrottle:'.config('common.sign_up_throttle_num').','.config('common.sign_up_throttle_expired') , 'repeatedSubmit'] , function ($api){
        $api->post('user/phone/signUp' , 'AuthController@handleSignUp')->name('user.phone.sign.up');
    });
    //游客模式生成用户
    $api->post('user/phone/signIn' , 'AuthController@signIn')->name('sign.in');
    $api->post('user/phone/code/signIn' , 'AuthController@handleSignIn')->name('user.phone.sign.in');
    $api->get('user/signOut' , 'AuthController@signOut')->name('sign.out');


    $api->group(['middleware'=>['refresh']] , function($api){


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


        $api->get('user/profile' , 'AuthController@me')->name('my.profile');

        $api->group(['middleware'=>['operationLog' , 'lastActive']] , function ($api){
            $api->get('ry/token' , 'RySetController@token')->name('ry.token');
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

        $api->post('user/{user}/block', 'UserController@block')->name('user.block');
        $api->post('user/{user}/unblock', 'UserController@unblock')->name('user.unblock');





        $api->resource('position' , 'PositionController', ['only' => ['store']]); //用户地址位置

        $api->post('device/update', 'DeviceController@update')->name('device.update');

        $api->put('app/mode/{mode}' , 'AppController@mode')->where('model', 'out|in')->name('app.mode');

    });
    $api->group(['middleware'=>['guestRefresh']] , function($api){

        $api->resource('feedback' , 'FeedbackController' , ['only' => ['store']]);
        $api->group(['middleware'=>['operationLog' , 'lastActive']] , function ($api){
            $api->resource('user' , 'UserController' , ['only' => ['show']]);
        });
    });


    $api->resource('device', 'DeviceController', ['only' => ['store']]);

    $api->get('user/{user}/type/{type}' , 'AuthController@accountVerification')->where('type', 'phone|nick_name')->name('user.account.verification');

    $api->get('user/{id}/ryStatus' , 'UserController@isRyOnline')->name('user.ry.online.status');
    $api->post('user/ry/online' , 'UserController@updateRyUserOnlineState')->name('user.ry.online.status.set');
    $api->get('user/ry/random' , 'UserController@randRyOnlineUser')->name('user.ry.online.random');


    $api->post('ry/chat' , 'RyChatController@store')->name('user.ry.message.store');

    $api->group(['middleware'=>['appAuth']] , function($api){
        $api->get('set/common' , 'SetController@commonSwitch')->name('set.common.switch');
    });
    $api->get('test/token' , 'TestController@token')->name('test.token');

});



