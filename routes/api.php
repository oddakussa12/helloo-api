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

    $api->resource('post' , 'PostController' , ['only' => ['index']]);

    $api->get('post/user/{user}' , 'PostController@showPostByUser');
    $api->get('post/top' , 'PostController@showTopList');
    $api->get('post/{uuid}' , 'PostController@showByUuid');
    $api->get('login/google', 'AuthController@redirectToProvider');
    $api->get('login/google/callback', 'AuthController@handleProviderCallback');
//    $api->post('test/auth' , '\Illuminate\Broadcasting\BroadcastController@authenticate');
//    $api->post('test/auth' , function (){
//        $manager = app(Illuminate\Broadcasting\BroadcastManager::class);
//        $driver = $manager->connection();
//        echo json_encode($driver->auth(request()));die;
//    });
//



    $api->get('postComment/post/{uuid}' , 'PostCommentController@showByPostUuid');


    $api->resource('category' , 'CategoryController');


    $api->put('user/{uuid}/follow' , 'UserController@follow');
    $api->put('user/{uuid}/unfollow' , 'UserController@unfollow');
    $api->post('user/signUp' , 'AuthController@signUp');
    $api->post('user/signIn' , 'AuthController@signIn');
    $api->get('user/signOut' , 'AuthController@signOut');
    $api->group(['middleware'=>'refresh'] , function($api){
        $api->get('user/profile' , 'AuthController@me');
        $api->get('post/usercenter/userself' , 'PostController@showPostByUserSelf');
        $api->put('user/updateuserinfo' , 'AuthController@updateUserInfo');
        $api->get('user/getqntoken' , 'UserController@getQiniuUploadToken');

        $api->group(['middleware'=>'throttle:30,1'] , function ($api){
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

        });
        $api->group(['middleware'=>'api.throttle' , 'limit' => 2, 'expires' => 1] , function ($api){
            $api->resource('postComment' , 'PostCommentController' , ['only' => ['store']]);
            $api->resource('post' , 'PostController' , ['only' => ['store']]);
        });
        $api->get('notification/count' , 'NotificationController@count');
        $api->put('notification/{id}' , 'NotificationController@read');
        $api->get('notification/{id}' , 'NotificationController@detail');
    });
    $api->get('notification' , 'NotificationController@index');
//    $api->get('english' , 'NotificationController@test');
//    $api->get('event' , 'EventController@index');
    $api->resource('user' , 'UserController' , ['only' => ['show','update']]);
//    $api->get('testt' , 'PostCommentController@test');

});



