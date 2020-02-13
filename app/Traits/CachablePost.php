<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

trait CachablePost
{
    /**
     * Fetches all the cachable data for the channel and put it in the cache.
     *
     * @param int $id
     *
     * @return void
     */
    protected function cacheChannelData($id)
    {
//        $post = Post::where('post_id', $id)->firstOrFail();
//
//        $postData = [
//            'commentsCount'    => $post->comments()->count(),
//            'country' => $post->subscriptions()->count(),
//        ];
//
//        Redis::hmset('post.'.$id.'.data', $postData);
//
//        return $postData;
    }

    public function likeCount($id)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'like';
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $likeData = \json_decode(Redis::hget($postKey, $field) , true);
        }else{
            $likeData = $this->initLikeCount($id);
        }
        if(!isset($likeData['tmp_like']))
        {
            $likeData['tmp_like'] = 0;
        }
        if(!isset($likeData['tmp_dislike']))
        {
            $likeData['tmp_dislike'] = 0;
        }
        return $likeData;
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
                $score = 1;
            }else{
                $score = $score+1;
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

    public function updateLikeCount($id , $type='like' , $tmpNum=0)
    {
        $postKey = 'post.'.$id.'.data';
        $field = 'like';
        if(Redis::exists($postKey)&&Redis::hexists($postKey , $field))
        {
            $likeData = \json_decode(Redis::hget($postKey, $field) , true);
        }else{
            $likeData = $this->initLikeCount($id);
        }
        if(in_array($type , array('like' , 'dislike')))
        {
            $likeType = $type;
            $likeCount = $likeData[$likeType]+1;
            if($tmpNum>0)
            {
                if(isset($likeData['tmp_'.$type]))
                {
                    $likeData['tmp_'.$type] = $likeData['tmp_'.$type]+$tmpNum;
                }else{
                    $likeData['tmp_'.$type] = $tmpNum;
                }
            }
        }else if($type=='revokeLike'){
            $likeType = 'like';
            $likeCount = $likeData[$likeType]-1;
        }else if($type=='revokeDislike'){
            $likeType = 'dislike';
            $likeCount = $likeData[$likeType]-1;
        }else{
            $likeType = 'like';
            $likeCount = $likeData[$likeType];
        }
        $likeData[$likeType] = $likeCount<0?0:$likeCount;
        $likeData = collect($likeData);
        Redis::hset($postKey , $field , $likeData);
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
        return collect(array('data'=>$country , 'total'=>$count));
    }
    public function updateCountry($id, $country , $add=true)
    {
        // we need to make sure the cached data exists
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
}
