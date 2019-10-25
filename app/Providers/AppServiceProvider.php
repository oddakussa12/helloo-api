<?php

namespace App\Providers;

use App\Models\Tag;
use App\Models\User;
use App\Models\Post;
use App\Models\Category;
use App\Models\PostComment;
use App\Models\PyChat;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\PostCommentRepository;
use App\Repositories\Contracts\PyChatRepository;
use App\Repositories\Eloquent\EloquentTagRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentPostRepository;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentPostCommentRepository;
use App\Repositories\Eloquent\EloquentPyChatRepository;


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
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind(UserRepository::class, function () {
            $repository = new EloquentUserRepository(new User());
            return $repository;
        });
        $this->app->bind(CategoryRepository::class, function () {
            $repository = new EloquentCategoryRepository(new Category());
            return $repository;
        });
        $this->app->bind(PostRepository::class, function () {
            $repository = new EloquentPostRepository(new Post());
            return $repository;
        });
        $this->app->bind(PostCommentRepository::class, function () {
            $repository = new EloquentPostCommentRepository(new PostComment());
            return $repository;
        });
        $this->app->bind(TagRepository::class, function () {
            $repository = new EloquentTagRepository(new Tag());
            return $repository;
        });
        $this->app->bind(PyChatRepository::class, function () {
            $repository = new EloquentPyChatRepository(new PyChat());
            return $repository;
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
