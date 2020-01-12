<?php

namespace App\Listeners;

use App\Models\PostViewNum;
use App\Traits\CachablePost;
use App\Events\PostViewCreated;

class PostViewCreatedListener
{
    use CachablePost;
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
        $this->updateViewVirtualCount($postView->post_id);
        $this->updateViewCount($postView->post_id);
    }

}
