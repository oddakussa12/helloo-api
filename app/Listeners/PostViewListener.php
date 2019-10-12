<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Events\PostViewEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        $view = array(
            'user_id'=>auth()->check()?auth()->id():0,
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

    /**
     * Handle the event.
     *
     * @param PostViewEvent $event
     * @param $exception
     * @return void
     */
    public function failed(PostViewEvent $event, $exception)
    {

    }
}
