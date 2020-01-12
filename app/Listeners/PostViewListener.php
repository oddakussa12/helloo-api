<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Events\PostViewEvent;

class PostViewListener
{
    /**
     * Create the event listener.
     *
     * @param $event
     */
    public function __construct()
    {
        //
    }

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
        $view = array(
            'user_id'=>empty($user)?0:$user->user_id,
            'post_view_ip'=>$event->getIp(),
        );
        $postView = $post->view()->where($view)->where('post_view_created_at' , '>' , Carbon::now()->subHours(1))->exists();
        if(!$postView)
        {
            $addresses = geoip($event->getIp());
            $view['view_country'] = $addresses->country;
            $view['view_state'] = $addresses->state_name;
            $view['view_city'] = $addresses->city;
            return $post->view()->create($view);
        }
    }

}
