<?php

namespace App\Listeners;

use App\Events\PostViewCreated;
use App\Events\PostViewEvent;
use App\Models\PostViewNum;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostViewCreatedListener
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
     * @param PostViewCreated $event
     * @return void
     */
    public function handle(PostViewCreated $event)
    {
        $postView = $event->getPostView();
        $postViewNum = PostViewNum::where('post_id' , $postView->post_id)->first();
        if(empty($postViewNum))
        {
            PostViewNum::create(array('post_id'=>$postView->post_id));
        }else{
            $postViewNum->increment('post_view_num');
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
