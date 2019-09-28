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
    $api->group(['middleware'=>'guestrefresh'] , function($api){
        $api->resource('post' , 'PostController' , ['only' => ['index']]);



        $api->get('post/user/{user}' , 'PostController@showPostByUser');
        $api->get('post/top' , 'PostController@showTopList');

        $api->get('login/google', 'AuthController@redirectToProvider');
        $api->get('login/google/callback', 'AuthController@handleProviderCallback');

        $api->resource('category' , 'CategoryController');
        $api->put('user/{uuid}/follow' , 'UserController@follow');
        $api->put('user/{uuid}/unfollow' , 'UserController@unfollow');
        $api->post('user/signUp' , 'AuthController@signUp');
        $api->post('user/signIn' , 'AuthController@signIn');
        $api->get('user/signOut' , 'AuthController@signOut');
        $api->get('postComment/post/{uuid}' , 'PostCommentController@showByPostUuid');
    });
    $api->group(['middleware'=>'refresh'] , function($api){

        $api->get('postComment/myself' , 'PostCommentController@myself');
        $api->get('postComment/like' , 'PostCommentController@mylike');

        $api->get('user/profile' , 'AuthController@me');
        $api->get('post/myself' , 'PostController@myself');
        $api->put('user/updateuserinfo' , 'AuthController@updateUserInfo');
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

        $api->group(['middleware'=>'throttle:2,5'] , function ($api){
            $api->resource('post' , 'PostController' , ['only' => ['store']]);
        });
        $api->group(['middleware'=>'throttle:6,1'] , function ($api){
            $api->resource('postComment' , 'PostCommentController' , ['only' => ['store']]);
        });
        $api->get('notification/count' , 'NotificationController@count');
        $api->put('notification/type/{type}' , 'NotificationController@readAll');
        $api->put('notification/{id}' , 'NotificationController@read');
        $api->get('notification/{id}' , 'NotificationController@detail');
    });
    $api->group(['middleware'=>'guestrefresh'] , function($api){
        $api->get('post/{uuid}' , 'PostController@showByUuid');
        $api->get('notification' , 'NotificationController@index');
        $api->resource('tag' , 'TagController' , ['only' => ['index' , 'store']]);
//    $api->get('english' , 'NotificationController@test');
        $api->get('event' , 'EventController@index');
        $api->resource('user' , 'UserController' , ['only' => ['show','update']]);
//    $api->get('/d/testt' , 'PostController@test');
    });


});



