<?php

namespace App\Traits;

use App\Models\Post;
use Illuminate\Support\Facades\Redis;

trait CachablePost
{
    public function initPost(Post $post)
    {
        $postKey = 'post.'.$post->getKey().'.data';
        $post_hotting = $post->post_hotting;
        $data = array(
            'post_uuid'=>$post->post_uuid,
            'user_id'=>$post->user_id,
            'post_type'=>$post->post_type,
            'post_hotting'=>in_array($post_hotting , array(0 , 1) , true)?$post_hotting:1,
            'post_created_at'=>$post->post_created_at,
            'post_default_locale'=>$post->post_default_locale,
            'post_content_default_locale'=>$post->post_content_default_locale,
            'post_event_country_id'=>$post->post_event_country_id,
            'post_media'=>\json_encode(empty($post->post_media)?[]:$post->post_media , JSON_UNESCAPED_UNICODE),
        );
        Redis::hmset($postKey , $data);
        $newKey = config('redis-key.post.post_index_new');
        Redis::zadd($newKey , strtotime(optional($post->post_created_at)->toDateTimeString()) , $post->getKey());
    }

    public function getPost($id , array $fields=array())
    {
        $postKey = 'post.'.$id.'.data';
        if(Redis::exists($postKey))
        {
            if(!empty($fields))
            {
                return array_combine($fields , Redis::hmget($postKey , $fields));
            }
            return Redis::hgetall($postKey);
        }
        return array();
    }

    public function likeCount($id)
    {
        $postKey = 'post.'.$id.'.data';
        $field = array('temp_like' , 'real_like' , 'real_dislike' , 'tmp_like' , 'tmp_dislike');
        $viewField = array('temp_like' , 'like' , 'dislike' , 'tmp_like' , 'tmp_dislike');
        $likeData = Redis::hmget($postKey, $field);
        $likeData = array_map(function ($v){
            return intval($v);
        },array_combine($viewField , $likeData));
        $indexSwitch = (bool)index_switch();
        if($indexSwitch)
        {
            $likeData['tmp_like'] = $likeData['temp_like'];
        }
        unset($likeData['temp_like']);
        return $likeData;
//        $postKey = 'post.'.$id.'.data';
//        $field = 'like';
//        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
//        {
//            $likeData = \json_decode(Redis::hget($postKey, $field) , true);
//        }else{
//            $likeData = $this->initLikeCount($id);
//        }
//        if(!isset($likeData['tmp_like']))
//        {
//            $likeData['tmp_like'] = 0;
//        }
//        if(!isset($likeData['tmp_dislike']))
//        {
//            $likeData['tmp_dislike'] = 0;
//        }
//        return $likeData;
    }

    public function viewCount($id)
    {
        $score = 0;
        $postKey = 'post_view_rank';
        if(Redis::exists($postKey))
        {
            $score = Redis::zscore($postKey, $id);
        }
        return intval($score);
    }

    public function viewVirtualCount($id)
    {
        $score = 0;
        $postKey = 'post_view_virtual_rank';
        if(Redis::exists($postKey))
        {
            $score = Redis::zscore($postKey, $id);
            if(!empty($score))
            {
                if(mt_rand(1 , 10000000)>7000000)
                {
                    $score = $score+mt_rand(1,5);
                    Redis::zadd($postKey , $score , $id);
                }
            }else{
                $score = post_view();
                Redis::zadd($postKey , $score , $id);
            }
        }
        if($score>10000)
        {
            $score = round($score/1000 , 1).'k';
        }
        return $score;
    }

    public function updateViewCount($id)
    {
        $postKey = 'post_view_rank';
        $add = 0;
        if(Redis::exists($postKey))
        {
            $score = Redis::zscore($postKey, $id);
            if(empty($score))
            {
                $add = 1;
            }else{
                $add = $score+1;
            }
        }
        return Redis::zadd($postKey , $add , $id);
    }

    public function updateViewVirtualCount($id)
    {
        $postKey = 'post_view_virtual_rank';
        $add = 0;
        if(Redis::exists($postKey))
        {
            $score = Redis::zscore($postKey, $id);
            if(!empty($score))
            {
                $num = $this->getRelativelyRealViewCount($score);
                $num = $num+1;
                $add = post_view($num);
            }else{
                $add = post_view();
            }
        }
        return Redis::zadd($postKey , $add , $id);
    }


    public function getRelativelyRealViewCount($view_count=1) {

        if($view_count<100)
        {
            $num = 1;
        }elseif ($view_count>=100&&$view_count<800)
        {
            $num = 2;
        }elseif ($view_count>=800&&$view_count<2000)
        {
            $num = 3;
        }elseif ($view_count>=2000&&$view_count<4000)
        {
            $num = 4;
        }elseif ($view_count>=4000&&$view_count<7000)
        {
            $num = 5;
        }elseif ($view_count>=7000&&$view_count<=8230)
        {
            $num = 6;
        }else{
            $num = floor($view_count/1370);
        }
        return $num;
    }


    public function initViewCount($id , $view=0)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'view';
        Redis::hset($postKey , $field , $view);
        return $view;
    }

    public function initLikeCount($id)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'like';
        $likeData = array('like'=>0,'dislike'=>0,'tmp_like'=>0,'tmp_dislike'=>0);
        $likeData = collect($likeData);
        Redis::hset($postKey , $field , $likeData);
        return $likeData;
    }

    public function isNewCountry($id , $country)
    {
        $num = 0;
        $postKey = 'post.'.$id.'.data';
        $field = 'country';
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $countryData = \json_decode(Redis::hget($postKey, $field) , true);
            if(array_key_exists($country ,$countryData))
            {
                $num = $countryData[$country];
            }
        }
        return $num;
    }

    public function updateLikeCount($id , $type='like' , $tmpNum=0 , $isAuth=false)
    {
        $postKey = 'post.'.$id.'.data';
        if($type=='revokeLike'){
            $likeType = 'real_like';
            Redis::hincrby($postKey , $likeType , -1);
        }else if($type=='revokeDislike'){
            $likeType = 'real_dislike';
            Redis::hincrby($postKey , $likeType , -1);
        }else{
            if(in_array($type , array('like' , 'dislike')))
            {
                $likeType = 'real_'.$type;
                $after = Redis::hincrby($postKey , $likeType , 1);
                if($tmpNum>0)
                {
                    Redis::hincrby($postKey , 'tmp_'.$type , $tmpNum);
                }
                if($type=='like'&&!$isAuth)
                {
                    $coefficient = floatval(Redis::get('fake_like_coefficient'));
                    Redis::hmset($postKey , array('temp_like'=>fakeLike($after , $coefficient)));
                }
            }
        }

//        $postKey = 'post.'.$id.'.data';
//        $field = 'like';
//        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
//        {
//            $likeData = \json_decode(Redis::hget($postKey, $field) , true);
//        }else{
//            $likeData = $this->initLikeCount($id);
//        }
//        if(in_array($type , array('like' , 'dislike')))
//        {
//            $likeType = $type;
//            $likeCount = $likeData[$likeType]+1;
//            if($tmpNum>0)
//            {
//                if(isset($likeData['tmp_'.$type]))
//                {
//                    $likeData['tmp_'.$type] = $likeData['tmp_'.$type]+$tmpNum;
//                }else{
//                    $likeData['tmp_'.$type] = $tmpNum;
//                }
//            }
//        }else if($type=='revokeLike'){
//            $likeType = 'like';
//            $likeCount = $likeData[$likeType]-1;
//        }else if($type=='revokeDislike'){
//            $likeType = 'dislike';
//            $likeCount = $likeData[$likeType]-1;
//        }else{
//            $likeType = 'like';
//            $likeCount = $likeData[$likeType];
//        }
//        $likeData[$likeType] = $likeCount<0?0:$likeCount;
//        $likeData = collect($likeData);
//        Redis::hset($postKey , $field , $likeData);
    }

    public function countryNum($id)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'country';
        $count = 0;
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $countryList = collect(\json_decode(Redis::hget($postKey, $field) , true));
            $count = $countryList->count();
        }
        return $count;
    }

    public function countryCount($id)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'country';
        $country = array();
        $count = 0;
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $countryList = collect(\json_decode(Redis::hget($postKey, $field) , true));
            $count = $countryList->count();
            $times = $count>10?10:$count;
            $countryList = $countryList->sort(function($x, $y){
                return true;
            })->take($times);

            $countries = config('countries');
            $country = $countryList->map(function($item , $key) use ($countries){
                return array('country_code'=>strtolower($countries[$key-1]) , 'country_num'=>$item);
            })->sortByDesc('country_num')->values();
        }
//        $version = request()->header('YooulVersion' , 0);
        if($count<=3)
        {
            $country = array();
            $count = 0;
        }
        return collect(array('data'=>$country , 'total'=>$count));
    }


    public function updateCountry($id, $country , $add=true)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'country';
        $countryData = collect();
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $countryData = \json_decode(Redis::hget($postKey, $field) , true);
            if(array_key_exists($country ,$countryData))
            {
                if($add)
                {
                    $countryData[$country] = $countryData[$country]+1;
                }else{
                    $countryData[$country] = $countryData[$country]-1;
                    if($countryData[$country]<=0)
                    {
                        unset($countryData[$country]);
                    }
                }
            }else{
                if($add)
                {
                    $countryData[$country] = 1;
                }
            }
            $countryData = collect($countryData);
        }else{
            if($add)
            {
                $countryData = collect(array($country=>1));
            }
        }
        Redis::hset($postKey , $field , $countryData);
    }

    public function commenterCount($id)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'commenter';
        $count = 0;
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $count = collect(\json_decode(Redis::hget($postKey, $field) , true))->count();
        }
        return $count;
    }

    public function isNewCommenter($id , $user)
    {
        $num = 0;
        $postKey = 'post.'.$id.'.data';
        $field = 'commenter';
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $commentData = \json_decode(Redis::hget($postKey, $field) , true);
            if(array_key_exists($user ,$commentData))
            {
                $num = $commentData[$user];
            }
        }
        return $num;
    }

    public function updateCommenter($id, $user , $add=true)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'commenter';
        $commentData = collect();
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $commentData = \json_decode(Redis::hget($postKey, $field) , true);
            if(array_key_exists($user ,$commentData))
            {
                if($add)
                {
                    $commentData[$user] = $commentData[$user]+1;
                }else{
                    $commentData[$user] = $commentData[$user]-1;
                    if($commentData[$user]<=0)
                    {
                        unset($commentData[$user]);
                    }
                }
            }else{
                if($add)
                {
                    $commentData[$user] = 1;
                }
            }
            $commentData = collect($commentData);
        }else{
            if($add)
            {
                $commentData = collect(array($user=>1));
            }
        }
        Redis::hset($postKey , $field , $commentData);
    }

    public function commentCount($id)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'comment_num';
        return intval(Redis::hget($postKey, $field));
    }

    public function updateCommentCount($id, $add=true)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'comment_num';
        $number = (bool)$add?1:-1;
        return Redis::hincrby($postKey , $field , $number);
    }
    public function updateTopicPostRate($id , $rate)
    {
        $postKey = 'post.'.$id.'.data';
        $topics = \json_decode(Redis::hget($postKey , 'topics') , true);
        !empty($topics)&&array_walk($topics , function($item , $index) use($id , $rate){
                $key = strval($item);
                Redis::zadd($key."_rate" , $rate , $id);
        });

    }

    public function getPostTopics($id)
    {
        $posts = $this->getPost($id , array('topics'));
        if(!empty($posts['topics']))
        {
            return \json_decode($posts['topics'] , true);
        }
        return array();
    }

    /**
     * @param $postId
     * @param $voteId
     * @param $userId
     * 投票 是否选了某个选项
     */
    public function voteChoose($postId, $voteId, $userId)
    {

    }
}
