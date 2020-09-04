<?php

namespace App\Http\Controllers\V1;

use App\Custom\RedisList;
use App\Traits\CachableUser;
use Dingo\Api\Http\Response;
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

    /**
     * flag   1: 官方话题 2:后台可控话题  0: 用户热门话题(此处不需要处理)
     * sort          倒序排序
     * topic_content 标题
     *
     * @return Response
     * 后台设置热门话题
     */
    public function setHotTopic()
    {
        /*$topics = [
            ['topic_content' => 'title1', 'sort'  => 3,'flag' => 1],
            ['topic_content' => 'title2', 'sort'  => 1,'flag' => 1],
            ['topic_content' => 'title0', 'sort'  => 2,'flag' => 1],
            ['topic_content' => 'title5', 'sort'  => 4,'flag' => 2],
            ['topic_content' => 'title6', 'sort'  => 6,'flag' => 2],
            ['topic_content' => 'title4', 'sort'  => 5,'flag' => 2],
        ];*/

        $topics = \json_decode(request()->input('topics' , '') , true);
        if (count($topics) != count($topics, 1)) {
            $hotTopics = 'hot_topic';
            if (!empty($topics)) {
                $topics = sortArrByManyField($topics, 'flag', SORT_DESC, 'sort', SORT_DESC);
                Redis::del($hotTopics);
                Redis::set($hotTopics, json_encode($topics, JSON_UNESCAPED_UNICODE));
            }
        }
        return $this->response->noContent();
    }

    /**
     * @return Response
     * 设置热门搜索
     */
    public function setHotSearch()
    {
        /*$titles = [
            ['title' => 'title1', 'sort'  => 3],
            ['title' => 'title2', 'sort'  => 1],
            ['title' => 'title0', 'sort'  => 2],
            ['title' => 'title5', 'sort'  => 4],
            ['title' => 'title6', 'sort'  => 6],
            ['title' => 'title4', 'sort'  => 5],
        ];*/

        $titles = \json_decode(request()->input('titles' , '') , true);

        if (count($titles) != count($titles, 1)) {
            $titles = collect($titles)->sortByDesc('sort')->toArray();
        }

        $hotSearch = 'hot_search';
        if(!empty($titles)) {
            Redis::del($hotSearch);
            Redis::set($hotSearch, json_encode($titles, JSON_UNESCAPED_UNICODE));
        }
        return $this->response->noContent();
    }

}
