<?php

namespace App\Http\Controllers\V1;

use App\Custom\RedisList;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Concerns\BuildsQueries;

class TestController extends BaseController
{
    //
    use BuildsQueries;
    public function index(Request $request)
    {
        app(PostRepository::class)->customFinePost();
//        var_dump(app(UserRepository::class)->hiddenPosts());
//        app(UserRepository::class)->updateHiddenPosts(auth()->id() , 'adfaljj-djlkaj');
//        $perPage = 2;
//
//        $redis = new RedisList();
//        $pageName = 'page';
//        $page = $request->input('page' , 1);
//        $index = $request->input('index' , 1);
//        $key = 'post_index_'.$index;
//
////        for($i=1;$i<=100;$i++)
////        {
////            $rand = mt_rand(1 , 10000);
////            $redis->zAdd('tester' , $rand, 'c'.$rand);
////        }
//        $offset = ($page-1)*$perPage;
//        $list = $redis->zRangByScore($key , '-inf' , '+inf' , true , array($offset , $perPage));
//        $total = $redis->zSize($key);
//        return $this->paginator($list, $total, $perPage, $page, [
//            'path' => Paginator::resolveCurrentPath(),
//            'pageName' => $pageName,
//        ]);
    }

    public function clearCache(Request $request)
    {
        Cache::forget('fine_post');
        app(PostRepository::class)->getFinePostIds();
        return $this->response->noContent();
    }
}
