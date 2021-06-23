<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\GoodsRepository;
use Illuminate\Database\Concerns\BuildsQueries;

class BusinessController extends BaseController
{
    use BuildsQueries;

    public function search(Request $request)
    {
        $userId = intval(auth()->id());
        $keyword = escape_like(strval($request->input('keyword' , '')));
        if(!empty($keyword))
        {
            $users = User::where('user_shop' , 1)->where('user_verified' , 1)->where(function ($query) use ($keyword) {
                $query->where('user_nick_name', 'like', "%{$keyword}%")->orWhere('user_name', 'like', "%{$keyword}%");
            })->limit(10)->get();
            $goods = Goods::where('name', 'like', "%{$keyword}%")->limit(10)->get();
            $goodsIds = $goods->pluck('id')->toArray();
            if(!empty($goodsIds))
            {
                if($userId>0)
                {
                    $likes = collect(DB::table('likes_goods')->where('user_id' , $userId)->whereIn('goods_id' , $goodsIds)->get()->map(function ($value){
                        return (array)$value;
                    }))->pluck('goods_id')->unique()->toArray();
                }else{
                    $likes = array();
                }
                $goods->each(function($g) use ($likes){
                    $g->likeState = in_array($g->id , $likes);
                });
            }
        }else{
            $goods = $users = collect();
        }
        !empty($keyword)&&BusinessSearchLog::dispatch($userId , $keyword)->onQueue('helloo_{business_search_logs}');
        $users->each(function($user){
            $user->userPoint = app(UserRepository::class)->findPointByUserId($user->user_id);
        });
        return $this->response->array(array(
            'data'=>array(
                'user'=>UserCollection::collection($users),
                'goods'=>AnonymousCollection::collection($goods)
            )
        ));
    }

    public function discovery(Request $request)
    {
        $deliveryUsers = app(UserRepository::class)->allWithBuilder()->where('user_activation' , 1)->where('user_shop' , 1)->where('user_verified' , 1)->where('user_delivery' , 1)->inRandomOrder()->limit(20)->get();
        $users = app(UserRepository::class)->allWithBuilder()->where('user_activation' , 1)->where('user_shop' , 1)->where('user_verified' , 1)->where('user_delivery' , 0)->inRandomOrder()->limit(20)->get();
        $users->each(function($user){
            $user->userPoint = app(UserRepository::class)->findPointByUserId($user->user_id);
        });
        $deliveryUsers->each(function($deliveryUser){
            $deliveryUser->userPoint = app(UserRepository::class)->findPointByUserId($deliveryUser->user_id);
        });
        $data = array('data'=>array(
            'live_shop'=>UserCollection::collection($users),
            'delivery_shop'=>UserCollection::collection($deliveryUsers),
        ));
        return $this->response->array($data);
    }

    public function home(Request $request)
    {
        $appends = array();
        $type = $request->input('type' , 'product');
        $order = $request->input('order' , 'popular');
        $appends['type'] = $type;
        $appends['order'] = $order;
        $pageName = 'page';
        $page     = intval($request->input($pageName, 1));
        $perPage  = intval($request->input('per_page', 10));
        $perPage = $perPage<10?10:$perPage;
        $perPage = $perPage>50?50:$perPage;
        $offset   = ($page-1) * $perPage;
        if($type=='product')
        {
            if($order=='new')
            {
                $goods = app(GoodsRepository::class)->allWithBuilder()->orderByDesc('created_at')->paginate($perPage , ['*'] , $pageName , $page)->appends($appends);
            }else{
                $key = 'helloo:discovery:'.$order.':products';
                if(Redis::exists($key))
                {
                    $total = Redis::zcard($key);
                    $goodsIds = Redis::zrevrangebyscore($key , '+inf' , '-inf' , array('withscores'=>true , 'limit'=>array($offset , $perPage)));
                    $goodsIds = array_keys($goodsIds);
                }else {
                    $total = 0;
                    $goodsIds = array();
                }
                $goods = app(GoodsRepository::class)->allWithBuilder()->whereIn('id' , $goodsIds)->get();
                $goods = $this->paginator($goods , $total, $perPage, $page, [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ])->appends($appends);
            }
            return AnonymousCollection::collection($goods);
        }elseif ($type=='shop')
        {
            if($order=='new')
            {
                $shops = app(UserRepository::class)->allWithBuilder()->where('user_activation' , 1)->where('user_shop' , 1)->where('user_verified' , 1)->where('user_delivery' , 0)->orderByDesc('user_created_at')->paginate($perPage , ['*'] , $pageName , $page)->appends($appends);
            }else{
                $key = 'helloo:discovery:'.$order.':shops';
                if(Redis::exists($key))
                {
                    $total = Redis::zcard($key);
                    $shopIds = Redis::zrevrangebyscore($key , '+inf' , '-inf' , array('withscores'=>true , 'limit'=>array($offset , $perPage)));
                    $shopIds = array_keys($shopIds);
                }else {
                    $total = 0;
                    $shopIds = array();
                }
                $shops = app(UserRepository::class)->allWithBuilder()->whereIn('user_id' , $shopIds)->get();
                $shops = $this->paginator($shops, $total, $perPage, $page, [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ])->appends($appends);
            }
            $shops->each(function($shop){
                $shop->userPoint = app(UserRepository::class)->findPointByUserId($shop->user_id);
            });
            return UserCollection::collection($shops);
        }else{
            $data = $this->paginator(collect(), 0, $perPage, $page, [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
            return AnonymousCollection::collection($data);
        }
    }
}
