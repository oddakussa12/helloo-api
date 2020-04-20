<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Traits\CachablePost;
use App\Events\PostViewEvent;
use Illuminate\Support\Facades\Redis;

class PostViewListener
{
    use CachablePost;
    /**
     * Handle the event.
     *
     * @param PostViewEvent $event
     * @return void
     */
    public function handle(PostViewEvent $event)
    {
        $post = $event->getPost();
        $user = $event->getUser();
        $user_id = empty($user)?0:$user->user_id;
        $post_id = $post->post_id;
        $ip = $event->getIp();
        $now = Carbon::now();
        $timestamp = $now->timestamp;
        $postViewLastListKey = 'post_view_last_list';
        $field = md5($post_id.$ip.$user_id);
        $score = Redis::zscore($postViewLastListKey , $field);
        if($score===null||Carbon::createFromTimestamp($score)->diffInRealHours($now)>1)
        {
            $todayViewLastListKey = 'post_view_'.$now->toDateString();
            Redis::zadd($postViewLastListKey , $timestamp , $field);
            Redis::rpush($todayViewLastListKey , \json_encode(array('ip'=>$ip , 'user_id'=>$user_id , 'post_id'=>$post_id)));
            $this->updateViewVirtualCount($post_id);
            $this->updateViewCount($post_id);
//            $addresses = geoip($ip);
//            $view = array(
//                'user_id'=>empty($user)?0:$user->user_id,
//                'post_view_ip'=>$event->getIp(),
//            );
//            $view['view_country'] = $addresses->country;
//            $view['view_state'] = $addresses->state_name;
//            $view['view_city'] = $addresses->city;
//            return $post->view()->create($view);
        }



//
//        $postView = $post->view()->where($view)->where('post_view_created_at' , '>' , Carbon::now()->subHours(1))->exists();
//        if(!$postView)
//        {
//            $addresses = geoip($event->getIp());
//            $view['view_country'] = $addresses->country;
//            $view['view_state'] = $addresses->state_name;
//            $view['view_city'] = $addresses->city;
//            return $post->view()->create($view);
//        }
    }

}
