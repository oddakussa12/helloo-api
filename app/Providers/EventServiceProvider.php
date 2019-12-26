<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
        'App\Events\SignupEvent' => [
            'App\Listeners\SignupListener',
        ],
        'App\Events\PostViewEvent' => [
            'App\Listeners\PostViewListener',
        ],

        'App\Events\PostViewCreated' => [
            'App\Listeners\PostViewCreatedListener',
        ],

        'App\Events\Liked' => [
            'App\Listeners\LikeListener',
        ],
        'App\Events\DisLiked' => [
            'App\Listeners\DisLikeListener',
        ],
        
        'App\Events\RemoveVote' => [
            'App\Listeners\RemoveVoteListener',
        ],

        'App\Events\PostCommentCreated' => [
            'App\Listeners\PostCommentCreatedListener',
        ],
        'App\Events\PostCommentDeleted' => [
            'App\Listeners\PostCommentDeletedListener',
        ],
        'App\Events\Follow' => [
            'App\Listeners\FollowListener',
        ],
        'App\Events\UnFollow' => [
            'App\Listeners\UnFollowListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
