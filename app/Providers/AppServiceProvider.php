<?php

namespace App\Providers;

use App\Models\Tag;
use App\Models\User;
use App\Models\UserTag;
use App\Models\UserFriend;
use App\Models\UserFriendRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\UserTagRepository;
use App\Repositories\Contracts\UserFriendRepository;
use App\Repositories\Eloquent\EloquentTagRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentUserTagRepository;
use App\Repositories\Eloquent\EloquentUserFriendRepository;
use App\Repositories\Contracts\UserFriendRequestRepository;
use App\Repositories\Eloquent\EloquentUserFriendRequestRepository;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191);
        $this->setLocalesConfigurations();
        if(!in_array(domain() , config('common.online_domain')))
        {
            \DB::listen(function ($query) {
                $tmp = str_replace('?', '"'.'%s'.'"', $query->sql);
                $qBindings = [];
                foreach ($query->bindings as $key => $value) {
                    if (is_numeric($key)) {
                        $qBindings[] = $value;
                    } else {
                        $tmp = str_replace(':'.$key, '"'.$value.'"', $tmp);
                    }
                }
                $tmp = vsprintf($tmp, $qBindings);
                $tmp = str_replace("\\", "", $tmp);
                \Log::info(' execution time: '.$query->time.'ms; '.$tmp."\n\n\t");
            });
        }
        $request = $this->app['request'];
        $isSecure = $this->app['request']->isSecure()||in_array(domain() , config('common.online_domain'))||env('REDIRECT_HTTPS'  , false);
        $request->server->set('HTTPS', $isSecure);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepository::class, function () {
            return new EloquentUserRepository(new User());
        });

        $this->app->bind(TagRepository::class, function () {
            return new EloquentTagRepository(new Tag());
        });

        $this->app->bind(UserTagRepository::class, function () {
            return new EloquentUserTagRepository(new UserTag());
        });

        $this->app->bind(UserFriendRepository::class, function () {
            return new EloquentUserFriendRepository(new UserFriend());
        });
        $this->app->bind(UserFriendRequestRepository::class, function () {
            return new EloquentUserFriendRequestRepository(new UserFriendRequest());
        });
    }

    private function setLocalesConfigurations()
    {
        $availableLocales = $this->app->config->get('laravellocalization.supportedLocales');;
        $laravelDefaultLocale = $this->app->config->get('app.locale');
        if (! in_array($laravelDefaultLocale, array_keys($availableLocales))) {
            $this->app->config->set('app.locale', array_keys($availableLocales)[0]);
        }
        $this->app->config->set('translatable.locales', array_keys($availableLocales));
    }

}
