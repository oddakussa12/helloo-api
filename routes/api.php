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
    'prefix' => LaravelLocalization::setLocale().'/api',
    'middleware'=>['cors'],
    'namespace' => 'App\\Http\\Controllers\V1',
];

$api->group($V1Params , function ($api){
    $api->group(['middleware'=>['guestRefresh' , 'operationLog']] , function($api){
        $api->resource('post' , 'PostController' , ['only' => ['index']]);
//        $api->get('test' , 'AuthController@test');
        $api->get('post/user/{user}' , 'PostController@showPostByUser')->name('show.post.by.user');

        $api->get('post/top' , 'PostController@top')->name('post.top');
        $api->get('post/hot' , 'PostController@hot')->name('post.hot');

//        $api->get('login/google', 'AuthController@redirectToProvider');
        $api->post('login/oauth/callback', 'AuthController@handleProviderCallback')->name('oauth.login');

//        $api->resource('category' , 'CategoryController');
        //聊天翻译单条写入
        $api->resource('pychattranslation', 'PyChatTranslationController', ['only' => ['store']]);
        //聊天房间添加
        $api->resource('pychatroom', 'PyChatRoomController',['only' => ['store']]);
        $api->post('pychat/showmassage/user', 'PyChatController@showMassageByUserId')->name('show.massage.by.userid');
        //获取房间内聊天记录
        $api->post('pychat/showmassage/room', 'PyChatController@showMassageByRoomUuid')->name('show.massage.by.room.uuid');
        $api->get('postComment/post/{uuid}' , 'PostCommentController@showByPostUuid')->name('show.comment.by.post');
    });

    $api->post('user/signUp' , 'AuthController@signUp')->name('sign.up');
    //游客模式生成用户
    $api->post('user/guestSignUp' , 'AuthController@guestSignUp')->name('guest.signin');
    $api->post('user/signIn' , 'AuthController@signIn')->name('sign.in');
    $api->get('user/signOut' , 'AuthController@signOut')->name('sign.out');


    $api->group(['middleware'=>['refresh' , 'operationLog']] , function($api){

        //聊天信息写入删除
        $api->resource('pychat', 'PyChatController',['only' => ['store','destroy']]);
        $api->get('postComment/myself' , 'PostCommentController@myself')->name('comment.myself');
        $api->get('postComment/like' , 'PostCommentController@mylike')->name('comment.mylike');

        $api->get('user/profile' , 'AuthController@me')->name('my.profile');
        $api->get('post/myself' , 'PostController@myself')->name('post.myself');
        $api->post('user/update/myself' , 'AuthController@update')->name('myself.update');
        $api->get('user/getqntoken' , 'UserController@getQiniuUploadToken')->name('qn.token');
        $api->get('user/myfollowrandtwo' , 'UserController@myFollowRandTwo')->name('follow.two');

        $api->put('post/{uuid}/favorite' , 'PostController@favorite')->name('post.favorite');
        $api->put('post/{uuid}/unfavorite' , 'PostController@unfavorite')->name('post.unFavorite');
        $api->put('post/{uuid}/like' , 'PostController@like')->name('post.like');
//                $api->put('post/{uuid}/dislike' , 'PostController@dislike');
        $api->put('post/{uuid}/revokeVote' , 'PostController@revokeVote')->name('post.revokeVote');
        $api->put('postComment/{comment_id}/like' , 'PostCommentController@like')->name('comment.like');
//                $api->put('postComment/{comment_id}/dislike' , 'PostCommentController@dislike');
        $api->put('postComment/{comment_id}/revokeVote' , 'PostCommentController@revokeVote')->name('comment.revokeVote');
        $api->put('postComment/{comment_id}/favorite' , 'PostCommentController@favorite')->name('comment.favorite');
        $api->put('postComment/{comment_id}/unfavorite' , 'PostCommentController@unfavorite')->name('comment.unFavorite');
        $api->get('user/myfollow' , 'UserController@myFollow')->name('myself.follow');
        $api->get('user/followme' , 'UserController@followMe')->name('myself.followMe');
        $api->put('user/{id}/follow' , 'UserController@follow')->name('user.follow');
        $api->put('user/{id}/unfollow' , 'UserController@unfollow')->name('user.unFollow');
        //其他人的关注&粉丝列表
        $api->get('user/{id}/myfollow' , 'UserController@otherMyFollow')->name('other.follow');
        $api->get('user/{id}/followme' , 'UserController@otherFollowMe')->name('other.followMe');
        $api->group(['middleware'=>'throttle:3,1'] , function ($api){
            $api->post('post' , 'PostController@store')->name('post.store');
        });
        $api->delete('post/{uuid}' , 'PostController@destroy')->name('post.delete');
        $api->group(['middleware'=>'throttle:6,1'] , function ($api){
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
        $api->resource('user' , 'UserController' , ['only' => ['show']]);
    });


});



