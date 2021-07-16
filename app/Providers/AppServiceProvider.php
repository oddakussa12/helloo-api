<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Event;
use App\Models\UserFriend;
use App\Models\Business\Goods;
use Godruoyi\Snowflake\Snowflake;
use App\Models\UserFriendRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Business\CategoryGoods;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\GoodsRepository;
use App\Repositories\Contracts\EventRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\UserFriendRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentGoodsRepository;
use App\Repositories\Eloquent\EloquentEventRepository;
use App\Repositories\Contracts\CategoryGoodsRepository;
use App\Repositories\Eloquent\EloquentUserFriendRepository;
use App\Repositories\Contracts\UserFriendRequestRepository;
use App\Custom\Uuid\Snowflake\Resolver\RedisSequenceResolver;
use App\Repositories\Eloquent\EloquentCategoryGoodsRepository;
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
        Schema::defaultStringLength(191);
        $this->setLocalesConfigurations();
        if(!in_array(domain() , config('common.online_domain')))
        {
            DB::listen(function ($query) {
                $tmp = str_replace('%', '$$', $query->sql);
                $tmp = str_replace('?', '"'.'%s'.'"', $tmp);
                $qBindings = [];
                foreach ($query->bindings as $key => $value) {
                    if (is_numeric($key)) {
                        $qBindings[] = $value;
                    } else {
                        $tmp = str_replace(':'.$key, '"'.$value.'"', $tmp);
                    }
                }
                !empty($qBindings)&&!empty($tmp)&&$tmp = vsprintf($tmp, $qBindings);
                $tmp = str_replace("\\", "", $tmp);
                $tmp = str_replace("$$", "%", $tmp);
                Log::info(' execution time: '.$query->time.'ms; '.$tmp."\n\n\t");
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
        $this->app->bind(UserFriendRepository::class, function () {
            return new EloquentUserFriendRepository(new UserFriend());
        });
        $this->app->bind(UserFriendRequestRepository::class, function () {
            return new EloquentUserFriendRequestRepository(new UserFriendRequest());
        });
        $this->app->bind(EventRepository::class, function () {
            return new EloquentEventRepository(new Event());
        });
        $this->app->bind(GoodsRepository::class, function () {
            return new EloquentGoodsRepository(new Goods());
        });
        $this->app->bind(CategoryGoodsRepository::class, function () {
            return new EloquentCategoryGoodsRepository(new CategoryGoods());
        });
        $this->app->singleton('snowflake', function () {
            return (new Snowflake())->setSequenceResolver((new RedisSequenceResolver())->setCachePrefix('helloo:snowflake:{sequence}:'));
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
