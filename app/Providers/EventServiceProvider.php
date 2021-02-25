<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [

        'App\Events\SignupEvent' => [
            'App\Listeners\SignupListener',
        ],
        'App\Events\Liked' => [
            'App\Listeners\LikeListener',
        ],
        'App\Events\DisLiked' => [
            'App\Listeners\DisLikeListener',
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
