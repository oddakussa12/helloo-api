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
    $api->group(['middleware'=>['guestRefresh' , 'operationLog']] , function($api){

        $api->resource('post', 'PostController', ['only' => ['index']]);

        $api->get('post/user/{user}' , 'PostController@showPostByUser')->name('show.post.by.user');

        $api->get('post/top' , 'PostController@top')->name('post.top');
        $api->get('post/fine' , 'PostController@fine')->name('post.fine');
        $api->get('post/hot' , 'PostController@hot')->name('post.hot');
        $api->post('post/autopost' , 'PostController@autoStorePost')->name('post.auto.store.post');

        $api->post('login/oauth/callback', 'AuthController@handleProviderCallback')->name('oauth.login');
        $api->get('postComment/more/{commentTopId}' , 'PostCommentController@moreComment')->name('show.more.comment');
        $api->get('postComment/locate/{commentId}' , 'PostCommentController@locateComment')->name('show.locate.comment');
        $api->get('postComment/post/{uuid}' , 'PostCommentController@showByPostUuid')->name('show.comment.by.post');

//        $api->resource('category' , 'CategoryController');

//        $api->resource('pychat', 'PyChatController', ['only' => ['store']]);
        $api->post('pychat/translation', 'PyChatController@store');

        $api->post('pychat/listtranslation', 'PyChatTranslationController@store');
        $api->post('pychat/chatimageinsert', 'PyChatController@chatImageStore');
        //聊天房间添加
//        $api->resource('pychatroom', 'PyChatRoomController',['only' => ['store']]);
//
//        $api->post('pychat/showmessage/user', 'PyChatController@showMessageByUserId')->name('show.message.by.userid');
        //获取房间内聊天记录
        $api->post('pychat/showmessage/room', 'PyChatController@showMessageByRoomUuid')->name('show.message.by.room.uuid');

    });
    $api->group(['middleware'=>'throttle:'.config('common.forget_password_throttle_num').','.config('common.forget_password_throttle_expired')] , function ($api){
        $api->post('user/forgetPwd' , 'AuthController@forgetPwd')->name('user.forget.pwd');
    });

    $api->post('user/resetPwd' , 'AuthController@resetPwd')->name('user.reset.pwd');

    $api->post('user/signUp' , 'AuthController@signUp')->name('sign.up');
    //游客模式生成用户
    $api->post('user/guestSignUp' , 'AuthController@guestSignUp')->name('guest.signin');
    $api->post('user/signIn' , 'AuthController@signIn')->name('sign.in');
    $api->get('user/signOut' , 'AuthController@signOut')->name('sign.out');
    $api->post('auth/signIn/mobile/{mobile}' , 'AuthController@signInSmsSend')->name('sign.in.sms.send');


    $api->group(['middleware'=>['refresh' , 'operationLog']] , function($api){
        $api->resource('report', 'ReportController',['only' => ['store']]);

        //聊天信息写入删除
        $api->resource('pychat', 'PyChatController',['only' => ['store','destroy']]);
        $api->get('postComment/myself' , 'PostCommentController@myself')->name('comment.myself');
        $api->get('postComment/like' , 'PostCommentController@mylike')->name('comment.mylike');

        $api->get('user/profile' , 'AuthController@me')->name('my.profile');
        $api->get('post/myself' , 'PostController@myself')->name('post.myself');
        $api->post('user/update/myself' , 'AuthController@update')->name('myself.update');
        $api->get('user/getqntoken' , 'UserController@getQiniuUploadToken')->name('qn.token');
        $api->get('user/myfollowrandtwo' , 'UserController@myFollowRandTwo')->name('follow.two');

        $api->post('user/{user}/block', 'UserController@block')->name('user.block');
        $api->post('post/{uuid}/block', 'PostController@block')->name('post.block');

//        $api->put('post/{uuid}/favorite' , 'PostController@favorite')->name('post.favorite');
//        $api->put('post/{uuid}/unfavorite' , 'PostController@unfavorite')->name('post.unFavorite');

        $api->group(['middleware'=>['throttle:'.config('common.post_like_throttle_num').','.config('common.post_like_throttle_expired') , 'blacklist']] , function ($api){
            $api->put('post/{uuid}/like' , 'PostController@like')->name('post.like');
        });

        $api->group(['middleware'=>['throttle:'.config('common.post_dislike_throttle_num').','.config('common.post_dislike_throttle_expired') , 'blacklist']] , function ($api){
            $api->put('post/{uuid}/dislike' , 'PostController@dislike')->name('post.dislike');
        });

        $api->put('post/{uuid}/revokeLike' , 'PostController@revokeLike')->name('post.revokeLike');
        $api->put('post/{uuid}/revokeDislike' , 'PostController@revokeDislike')->name('post.revokeDislike');
        $api->group(['middleware'=>['throttle:'.config('common.post_comment_like_throttle_num').','.config('common.post_comment_like_throttle_expired') , 'blacklist']] , function ($api) {
            $api->put('postComment/{comment_id}/like', 'PostCommentController@like')->name('comment.like');
        });
//                $api->put('postComment/{comment_id}/dislike' , 'PostCommentController@dislike');
        $api->put('postComment/{comment_id}/revokeVote' , 'PostCommentController@revokeVote')->name('comment.revokeVote');
//        $api->put('postComment/{comment_id}/favorite' , 'PostCommentController@favorite')->name('comment.favorite');
//        $api->put('postComment/{comment_id}/unfavorite' , 'PostCommentController@unfavorite')->name('comment.unFavorite');
        $api->get('user/myfollow' , 'UserController@myFollow')->name('myself.follow');
        $api->get('user/followme' , 'UserController@followMe')->name('myself.followMe');
        $api->put('user/{id}/follow' , 'UserController@follow')->name('user.follow');
        $api->put('user/{id}/unfollow' , 'UserController@unfollow')->name('user.unFollow');
        //其他人的关注&粉丝列表
        $api->get('user/{id}/myfollow' , 'UserController@otherMyFollow')->name('other.follow');
        $api->get('user/{id}/followme' , 'UserController@otherFollowMe')->name('other.followMe');
        $api->group(['middleware'=>['throttle:'.config('common.post_throttle_num').','.config('common.post_throttle_expired') , 'blacklist']] , function ($api){
            $api->post('post' , 'PostController@store')->name('post.store');
        });
        $api->delete('post/{uuid}' , 'PostController@destroy')->name('post.delete');
        $api->group(['middleware'=>['throttle:'.config('common.post_comment_throttle_num').','.config('common.post_comment_throttle_expired') , 'blacklist']] , function ($api){
            $api->post('postComment' , 'PostCommentController@store')->name('comment.store');
        });
        $api->resource('postComment' , 'PostCommentController' , ['only' => ['destroy']]);
        $api->get('notification/count' , 'NotificationController@count')->name('notice.count');
        $api->put('notification/type/{type}' , 'NotificationController@readAll')->name('notice.readAll');
        $api->put('notification/{id}' , 'NotificationController@read')->name('notice.read');
        $api->get('notification/{id}' , 'NotificationController@detail')->name('notice.detail');
    });
    $api->group(['middleware'=>['guestRefresh' , 'operationLog']] , function($api){
        $api->get('user/userranking' , 'UserController@rank')->name('user.rank');

        $api->get('postComment/user/{user}' , 'PostCommentController@showPostCommentByUser')->name('show.comment.by.user');

        $api->get('postComment/like/{user}' , 'PostCommentController@showPostCommentLikeByUser')->name('show.like.comment.by.user');
        $api->resource('feedback' , 'FeedbackController' , ['only' => ['store']]);
        $api->get('post/{uuid}' , 'PostController@showByUuid')->name('post.show');
        $api->get('notification' , 'NotificationController@index')->name('notification.index');
        $api->resource('tag' , 'TagController' , ['only' => ['index' , 'store']]);
        $api->get('tag/hot' , 'TagController@hot')->name('tag.hot');
        $api->get('event' , 'EventController@index')->name('event.index');
        $api->get('roomtopic' , 'EventController@roomTopic')->name('event.roomtopic');
        $api->resource('user' , 'UserController' , ['only' => ['show']]);
    });
    $api->post('message/translate' , 'PrivateMessageController@translate')->name('private.message.translate');
    $api->post('message/push' , 'PrivateMessageController@push')->name('message.push');
    $api->get('message/token' , 'PrivateMessageController@token')->name('message.token');
    $api->resource('device', 'DeviceController', ['only' => ['store']]);

    $api->get('user/{user}/type/{type}' , 'AuthController@accountExists')->where('type', 'email|name')->name('user.account.exists');
    $api->get('user' , 'UserController@index')->name('user.name.search');
    $api->get('user/name/{name}/email/{email}/cancelled' , 'UserController@cancelled')->name('user.account.cancelled');
    $api->get('app/clear/cache' , 'AppController@clearCache')->name('app.clear.cache');
    $api->get('app/version' , 'AppController@index')->name('app.index');
    $api->get('rong/state/user/{id}' , 'PrivateMessageController@userCheckOnline')->name('rong.user.is_online');
    $api->get('set/post/rate' , 'SetController@postRate')->name('set.post.rate');
    $api->get('post/{uuid}/country' , 'PostController@country')->name('post.country');
});



