<?php

namespace App\Http\Controllers\V1;

use App\Custom\RedisList;
use App\Traits\CachableUser;
use Illuminate\Http\Request;
use App\Events\PostCommentDeleted;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

class BackStageController extends BaseController
{
    use CachableUser;
    /**
     * @var PostCommentRepository
     */
    private $postComment;

    /**
     * @var UserRepository
     */
    private $user;

    /**
     * @var PostRepository
     */
    private $post;

    public function __construct(PostCommentRepository $postComment , UserRepository $user , PostRepository $post)
    {
        $this->postComment = $postComment;
        $this->user = $user;
        $this->post = $post;
    }

    public function index()
    {

    }

    public function destroyComment($id)
    {
        $postComment = $this->postComment->findOrFail($id);
        $user = $this->user->find($postComment->user_id);
        event(new PostCommentDeleted($user , $postComment));
        $this->postComment->destroy($postComment);
        return $this->response->noContent();
    }

    public function destroyPost($uuid)
    {
        $post = $this->post->findOrFailByUuid($uuid);
        $this->post->destroy($post);
        $redis = new RedisList();
        $user = $this->user->find($post->user_id);
        if($post->post_created_at>config('common.score_date'))
        {
            $user->decrement('user_score' , 2);
            $userScoreRankKey = config('redis-key.user.score_rank');
            $redis->zIncrBy($userScoreRankKey , -2 , $user->user_id);
        }
        $userPostsKey = config('redis-key.user.posts');
        $redis->zIncrBy($userPostsKey , -1 , $user->user_id);
        $postKey = config('redis-key.post.post_index_new');
        $essencePostKey = config('redis-key.post.post_index_essence');
        $essenceManualPostKey = config('redis-key.post.post_index_essence_customize');
        $rateKeyOne = config('redis-key.post.post_index_rate').'_1';
        $rateKeyTwo = config('redis-key.post.post_index_rate').'_2';
        $redis->zRem($postKey , $post->getKey());
        $redis->zRem($rateKeyOne , $post->getKey());
        $redis->zRem($rateKeyTwo , $post->getKey());
        $redis->zRem($essencePostKey , $post->getKey());
        $redis->zRem($essenceManualPostKey , $post->getKey());
        return $this->response->noContent();
    }

    public function getCustomEssencePost()
    {
        $posts = $this->post->allWithBuilder();
        $posts = $this->post->getCustomEssencePost($posts);
        return $this->response->item($posts);
    }


    public function setCustomEssencePost(Request $request , $postId)
    {
        $score = intval($request->input('score' , mt_rand(11111 , 99999)));
        $operation = (bool)$request->input('operation' , true);
        $this->post->setCustomEssencePost($postId , $operation , $score);
        return $this->response->noContent();
    }

    public function setCarousel(Request $request , $postUuid)
    {
        $locale = (string)$request->input('locale' , '');
        $image = (string)$request->input('image' , '');
        $postId = (int)$request->input('post_id' , '');
        if(!empty($locale)&&!empty($image))
        {
            carousel_post($postUuid , $locale , $image);
        }else{
            non_carousel_post($postUuid);
            $this->post->setNonFinePost($postId , true);
        }
        return $this->response->noContent();
    }

    public function setFollowUser(Request $request , $followed)
    {
        $fans = $request->input('fans' , '');
        $fans = \json_decode($fans , true);
        $count = count($fans);
        if(!empty($fans)&&$count>0)
        {
            $this->updateUserFollowMeCount($followed , $count);
            foreach ($fans as $fan)
            {
                $this->updateUserMyFollowCount($fan);
            }
        }
        return $this->response->noContent();
    }

    public function setNonFinePost($postId)
    {
        $flag = (bool)request()->input('flag' , 0);
        $this->post->setNonFinePost($postId , $flag);
        return $this->response->noContent();
    }

    public function setHotTopic()
    {
        $topics = \json_decode(request()->input('topics' , '') , true);
        $hotTopics = 'hot_topic';
        if(!empty($topics))
        {   $now = time();
            Redis::del($hotTopics);
            Redis::pipeline(function ($pipe) use ($topics , $hotTopics , $now){
                array_walk($topics , function($item , $index) use ($pipe , $hotTopics , $now){
                    $key = strval($item);
                    $pipe->zadd($hotTopics , $now , $key);
                });
            });
        }
        return $this->response->noContent();
    }

    public function setHotSearch()
    {
        $titles = \json_decode(request()->input('titles' , '') , true);
        $hotSearch = 'hot_search';
        if(!empty($titles))
        {
            Redis::del($hotSearch);
            Redis::set($hotSearch,json_encode($titles, JSON_UNESCAPED_UNICODE));
        }
        return $this->response->noContent();
    }
}
