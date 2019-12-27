<?php

namespace App\Traits;

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
        if(!isset($likeData['tpm_dislike']))
        {
            $likeData['tmp_dislike'] = 0;
        }
        return $likeData;
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
