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
    $api->group(['middleware'=>'guestRefresh'] , function($api){
        $api->resource('post' , 'PostController' , ['only' => ['index']]);
        $api->get('test' , 'AuthController@test');
        $api->get('post/user/{user}' , 'PostController@showPostByUser');

        $api->get('post/top' , 'PostController@showTopList');
        $api->get('post/hot' , 'PostController@hot');

        $api->get('login/google', 'AuthController@redirectToProvider');
        $api->post('login/oauth/callback', 'AuthController@handleProviderCallback');

        $api->resource('category' , 'CategoryController');
        $api->resource('pychat', 'PyChatController');
        $api->post('pychat/showmassage', 'PyChatController@showMassageByUserId');
        $api->get('postComment/post/{uuid}' , 'PostCommentController@showByPostUuid');
    });

    $api->post('user/signUp' , 'AuthController@signUp');
    $api->post('user/signIn' , 'AuthController@signIn');
    $api->get('user/signOut' , 'AuthController@signOut');
    $api->get('user/userranking' , 'UserController@userRanking');

    $api->group(['middleware'=>'refresh'] , function($api){

        $api->get('postComment/myself' , 'PostCommentController@myself');
        $api->get('postComment/like' , 'PostCommentController@mylike');


        $api->get('user/profile' , 'AuthController@me');
        $api->get('post/myself' , 'PostController@myself');
        $api->post('user/update/myself' , 'AuthController@update');
        $api->get('user/getqntoken' , 'UserController@getQiniuUploadToken');

        $api->put('post/{uuid}/favorite' , 'PostController@favorite');
        $api->put('post/{uuid}/unfavorite' , 'PostController@unfavorite');
        $api->put('post/{uuid}/like' , 'PostController@like');
//                $api->put('post/{uuid}/dislike' , 'PostController@dislike');
        $api->put('post/{uuid}/revokeVote' , 'PostController@revokeVote');
        $api->put('postComment/{comment_id}/like' , 'PostCommentController@like');
//                $api->put('postComment/{comment_id}/dislike' , 'PostCommentController@dislike');
        $api->put('postComment/{comment_id}/revokeVote' , 'PostCommentController@revokeVote');
        $api->put('postComment/{comment_id}/favorite' , 'PostCommentController@favorite');
        $api->put('postComment/{comment_id}/unfavorite' , 'PostCommentController@unfavorite');
        $api->get('user/myfollow' , 'UserController@myFollow');
        $api->get('user/followme' , 'UserController@followMe');
        $api->put('user/{id}/follow' , 'UserController@follow');
        $api->put('user/{id}/unfollow' , 'UserController@unfollow');
        //其他人的关注&粉丝列表
        $api->get('user/{id}/myfollow' , 'UserController@otherMyFollow');
        $api->get('user/{id}/followme' , 'UserController@otherFollowMe');
        $api->group(['middleware'=>'throttle:3,1'] , function ($api){
            $api->post('post' , 'PostController@store');
        });
        $api->delete('post/{uuid}' , 'PostController@destroy');
        $api->group(['middleware'=>'throttle:6,1'] , function ($api){
            $api->post('postComment' , 'PostCommentController@store');
        });
        $api->resource('postComment' , 'PostCommentController' , ['only' => ['destroy']]);
        $api->get('notification/count' , 'NotificationController@count');
        $api->put('notification/type/{type}' , 'NotificationController@readAll');
        $api->put('notification/{id}' , 'NotificationController@read');
        $api->get('notification/{id}' , 'NotificationController@detail');
    });
    $api->group(['middleware'=>'guestRefresh'] , function($api){
        $api->get('postComment/user/{user}' , 'PostCommentController@showPostCommentByUser');
        $api->get('postComment/like/{user}' , 'PostCommentController@showPostCommentLikeByUser');
        $api->resource('feedback' , 'FeedbackController' , ['only' => ['store']]);
        $api->get('post/{uuid}' , 'PostController@showByUuid');
        $api->get('notification' , 'NotificationController@index');
        $api->resource('tag' , 'TagController' , ['only' => ['index' , 'store']]);
        $api->get('tag/hot' , 'TagController@hot');
        $api->get('event' , 'EventController@index');
        $api->resource('user' , 'UserController' , ['only' => ['show']]);
    });


});



