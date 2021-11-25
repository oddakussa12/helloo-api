<?php

namespace App\Http\Controllers\V1\Business;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use App\Jobs\BusinessGoodsLog;
use App\Jobs\Bitrix24Product;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
use Illuminate\Validation\Rule;
use App\Resources\UserCollection;
use App\Jobs\GoodsCategoryUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Business\CategoryGoods;
use App\Resources\AnonymousCollection;
use App\Models\Business\GoodsCategory;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Validation\ValidationException;
use App\Repositories\Contracts\GoodsRepository;
use Illuminate\Database\Concerns\BuildsQueries;
use App\Repositories\Contracts\CategoryGoodsRepository;
use App\Models\Business\SpecialGoods;

class GoodsController extends BaseController
{
    use BuildsQueries;

    /**
     * @note 我的商品
     * @datetime 2021-07-12 17:53
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $keyword = escape_like(strval($request->input('keyword' , '')));
        $auth = intval(auth()->id());
        $userId = strval($request->input('user_id' , ''));
        $type = strval($request->input('type' , ''));
        $version = $request->input('version' , 'v1');
        $categoryId = intval($request->input('category_id' , ''));
        $appends['keyword'] = $keyword;
        $appends['user_id'] = $userId;
        $appends['type']    = $type;
        $appends['version']    = $version;
        if(!empty($keyword))
        {
            $goods = Goods::where('user_id', $userId)->where('status' , 1)->where('name', 'like', "%{$keyword}%")->limit(10)->get();
            BusinessSearchLog::dispatch($auth , $keyword , $userId)->onQueue('helloo_{business_search_logs}');
        }elseif (!empty($userId))
        {
            if($version=='v1')
            {
                $perPage  = intval($request->input('per_page', 10));
                $perPage = $perPage<10||$perPage>50?20:$perPage;
                $appends['per_page'] = $perPage;
                $goods = Goods::where('user_id', $userId);
                $type != 'management' && $goods = $goods->where('status' , 1);
                $goods = $goods->orderByDesc('created_at')->paginate($perPage)->appends($appends);
            }else{
                $data = array();
                $categories = app(CategoryGoodsRepository::class)->findByUserId($userId);
                if(!empty($categoryId))
                {
                    $categories = collect($categories)->where('category_id' , $categoryId)->toArray();
                }
                $goods = Goods::where('user_id' , $userId)->where('status' , 1)->limit(100)->get();
                foreach ($categories as $category)
                {
                    $gIds = $category['goods_ids'];
                    $gData = $goods->whereIn('id' , array_keys($gIds));
                    $gData = $gData->each(function ($g) use ($gIds){
                        $g->sort = $gIds[$g->id];
                    })->sortByDesc('sort');
                    array_push($data , array(
                        'category_id'=>$category['category_id'],
                        'name'=>$category['name'],
                        'default'=>$category['default'],
                        'is_default'=>$category['is_default'],
                        'sort'=>$category['sort'],
                        'goods'=>$gData->values()->toArray()
                    ));
                }
                if(empty($categoryId))
                {
                    $goodsIds = collect($categories)->pluck('goods_ids')->collapse()->keys()->unique()->toArray();
                    $diffGoodsIds = array_diff($goods->pluck('id')->unique()->toArray() , $goodsIds);
                    if(!empty($diffGoodsIds))
                    {
                        array_push($data , array(
                            'category_id'=>"",
                            'name'=>'undefined',
                            'default'=>1,
                            'is_default'=>true,
                            'sort'=>9999,
                            'goods'=>$goods->whereIn('id' , $diffGoodsIds)->values()->toArray()
                        ));
                    }
                }
                return AnonymousCollection::collection(collect($data)->sortByDesc('sort')->sortByDesc('default')->values());
            }
        }else{
            $goods = collect();
        }
        $goodsIds = $goods->pluck('id')->toArray();
        if(!empty($goodsIds))
        {
            $likes = $auth>0?collect(DB::table('likes_goods')->where('user_id' , $auth)->whereIn('goods_id' , $goodsIds)->get()->map(function ($value){
                return (array)$value;
            }))->pluck('goods_id')->unique()->toArray():array();
            $goods->each(function($g) use ($likes){
                $g->likeState = in_array($g->id , $likes);
            });
        }
        $goods->each(function($good){
            $discount_price = 0;
            $good->discount_price = $this->discountPrice($good->id);

        });
        return AnonymousCollection::collection($goods);
    }
    public function discountPrice($good_id){
        $discount_price = SpecialGoods::select('special_price')->where('goods_id',$good_id)->first();
        if($discount_price != null){
            return $discount_price->special_price;
        }else{
            return null;
        }
    }

    /**
     * @note 未分类商品
     * @datetime 2021-07-12 17:53
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function uncategorized(Request $request)
    {
        $userId = auth()->id();
        $categories = app(CategoryGoodsRepository::class)->findByUserId($userId);
        $goodsIds = collect($categories)->pluck('goods_ids')->collapse()->keys()->unique()->toArray();
        $goods = Goods::where('user_id' , $userId)->where('status' , 1)->limit(100)->get();
        $diffGoodsIds = array_diff($goods->pluck('id')->toArray() , $goodsIds);
        $goods = $goods->whereIn('id' , $diffGoodsIds)->values();
        return AnonymousCollection::collection($goods);
    }

    /**
     * @note 商品推荐
     * @datetime 2021-07-12 17:53
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function recommendation()
    {
        $userId = auth()->id();
        $goods = Goods::where('status' , 1)->select('id', 'user_id', 'name' , 'image' , 'like' , 'price' , 'currency' , 'point' , 'comment')->where('recommend', 1)->orderByDesc('recommended_at')->limit(10)->get();
        if ($goods->isEmpty()) {
            $goods = Goods::where('status' , 1)->select('id', 'user_id', 'name' , 'image' , 'like' , 'price' , 'currency' , 'point' , 'comment')->orderBy(DB::raw('rand()'))->limit(10)->get();
        }
        $goodsIds = $goods->pluck('id')->toArray();
        if(!empty($goodsIds))
        {
            $likes = collect(DB::table('likes_goods')->where('user_id' , $userId)->whereIn('goods_id' , $goodsIds)->get()->map(function ($value){
                return (array)$value;
            }))->pluck('goods_id')->unique()->toArray();
            $goods->each(function($g) use ($likes){
                $g->likeState = in_array($g->id , $likes);
            });
        }
        return AnonymousCollection::collection($goods);
    }

    /**
     * @note 商品信息
     * @datetime 2021-07-12 17:54
     * @param $id
     * @return AnonymousCollection
     */
    public function show($id)
    {
        $userId = intval(auth()->id());
        $action = strval(request()->input('action' , ''));
        $referrer = strval(request()->input('referrer' , ''));
        $goods = Goods::where('id' , $id)->firstOrFail();
        $user = app(UserRepository::class)->findByUserId($goods->user_id);
        $goods->user = new UserCollection($user);
        $like = !empty($userId)&&DB::table('likes_goods')->where('id' , strval(auth()->id())."-".$id)->first();
        $goods = $goods->makeVisible('status');
        $goods->likeState = !empty($like);
        if($action=='view'&&$goods->user_id!=$userId)
        {
            BusinessGoodsLog::dispatch($userId , $goods->user_id , $id , $referrer)->onQueue('helloo_{business_goods_logs}');
        }
        $categoryGoods = CategoryGoods::where('goods_id', $id)->first();
        if(!empty($categoryGoods))
        {
            $goods->goodsCategory = new AnonymousCollection(GoodsCategory::where('category_id' , $categoryGoods->category_id)->select('category_id' , 'name' , 'default')->first());
        }
        return new AnonymousCollection($goods);
    }

    /**
     * @note 商品增加
     * @datetime 2021-07-12 17:54
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $name = strval($request->input('name' , ''));
        $image = $request->input('image' , '');
        $price = $request->input('price');
        $status = $request->input('status');
        $categoryId = $request->input('category_id');
        $discountedPrice = $request->input('discounted_price');
        $description = strval($request->input('description' , ''));
        $packaging_cost = floatval($request->input('packaging_cost' , 0));
        $rules = [
            'name' => [
                'bail',
                'required',
                'string',
                'between:1,32'
            ],
            'image' => [
                'bail',
                'required',
                'array',
                'between:1,3'
            ],
            'price' => [
                'bail',
                'required',
                'numeric',
                'min:0'
            ],
            'description' => [
                'bail',
                'present',
                'string',
                'between:0,300'
            ],
            'status' => [
                'bail',
                'required',
                Rule::in(array(1 , 0 , '1' , '0'))
            ],
            'discounted_price' => [
                'bail',
                'filled',
                'numeric',
                'min:0'
            ],
            'packaging_cost' => [
                'bail',
                'filled',
                'numeric',
                'min:0',
                'max:9999'
            ]
        ];
        $data = $validationField = array(
            'user_id'=>$userId,
            'name'=>$name,
            'image'=>$image,
            'price'=>$price,
            'description'=>$description,
            'status'=>$status,
            'packaging_cost'=>round($packaging_cost , 2)
        );
        if(!empty($categoryId))
        {
            $goodsCategory = GoodsCategory::where('category_id' , $categoryId)->first();
            if(empty($goodsCategory)||$goodsCategory->user_id!=$userId)
            {
                abort(422 , 'Category not available!');
            }
            if($discountedPrice!==null&&$goodsCategory->is_default)
            {
                $validationField['discounted_price'] = $data['discounted_price'] = round(floatval($discountedPrice) , 2);
            }
        }
        try {
            Validator::make($validationField, $rules)->validate();
        } catch (ValidationException $exception) {
            throw new ValidationException($exception->validator);
        }

        $image = array_map(function($v){
            unset($v['path']);
            return $v;
        } , $image);
        $now = date("Y-m-d H:i:s");
        $goodsId = Uuid::uuid1()->toString();
        $data['id'] = $goodsId;
        $data['user_id'] = $userId;
        $data['image'] = \json_encode($image , JSON_UNESCAPED_UNICODE);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        if(empty($user->user_currency))
        {
            $phone = DB::table('users_phones')->where('user_id' , $user->user_id)->first();
            if(!empty($phone)&&$phone->user_phone_country=='251')
            {
                $data['currency'] = 'BIRR';
            }else
            {
                $data['currency'] = 'USD';
            }
        }else{
            $data['currency'] = $user->user_currency;
        }
        try{
            DB::beginTransaction();
            $goodsResult = DB::table('goods')->insert($data);
            if(!$goodsResult)
            {
                abort(500 , 'goods insert failed!');
            }
            if(!empty($goodsCategory))
            {
                $categoryGoodsData = array(
                    'id'=>app('snowflake')->id(),
                    'category_id'=>$goodsCategory->category_id,
                    'goods_id'=>$goodsId,
                    'user_id'=>$userId,
                    'created_at'=>$now,
                );
                $status!==null&&$categoryGoodsData['status'] = $status;
                $categoryGoodsResult = DB::table('categories_goods')->insert($categoryGoodsData);
                if(!$categoryGoodsResult)
                {
                    abort(500 , 'category goods insert failed!');
                }
                $goodsCategoryResult = DB::table('goods_categories')->where('category_id' , $goodsCategory->category_id)->increment('goods_num');
                if($goodsCategoryResult<=0)
                {
                    abort(500 , 'goods category increment failed!');
                }
                Redis::del("helloo:business:goods:category:service:account:".$userId);
            }
            DB::commit();
            $this->dispatch((new Bitrix24Product($data , __FUNCTION__))->onQueue('helloo_{bitrix_product}'));
            if($data['status']==1)
            {
                $price = $data['currency']=="BIRR"?$data['price']*0.023:$data['price'];
                Redis::zadd("helloo:discovery:price:products" , array(
                    $data['id']=>$price
                ));
            }
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('goods_add_fail' , array(
                'message'=>$e->getMessage(),
                'user_id'=>$userId,
                'data'=>$data,
            ));
        }
        return $this->response->created();
    }

    /**
     * @note 商品更新
     * @datetime 2021-07-12 17:54
     * @param Request $request
     * @param $id
     * @return \Dingo\Api\Http\Response
     * @throws ValidationException
     */
    public function update(Request $request , $id)
    {
        $user = auth()->user();
        $goods = Goods::where('id' , $id)->firstOrFail();
        $sort = intval($request->input('sort' , 0));
        $categoryId = $request->input('category_id');
        $discountedPrice = $request->input('discounted_price');
        $packaging_cost = $request->input('packaging_cost');
        $params = $validationField = $request->only(array('name' , 'image' , 'price' , 'status' , 'description'));
        $validationField['user_id'] = $goods->user_id;
        $rules = [
            'user_id' => [
                'bail',
                'filled',
                function ($attribute, $value, $fail) use ($user , $goods){
                    if($goods->user_id!=$user->user_id)
                    {
                        $fail('This goods does not exist!');
                    }
                }
            ],
            'name' => [
                'bail',
                'filled',
                'string',
                'between:6,24'
            ],
            'image' => [
                'bail',
                'filled',
                'array',
                'between:1,3'
            ],
            'price' => [
                'bail',
                'filled',
                'numeric',
                'min:0'
            ],
            'description' => [
                'bail',
                'filled',
                'string',
                'between:0,300'
            ],
            'status' => [
                'bail',
                'filled',
                Rule::in(array(1 , 0 , '1' , '0'))
            ],
            'discounted_price' => [
                'bail',
                'filled',
                'numeric',
                'min:0'
            ]
        ];
        if(!empty($categoryId))
        {
            $goodsCategory = GoodsCategory::where('category_id' , $categoryId)->first();
            if(empty($goodsCategory)||$goodsCategory->user_id!=$user->user_id)
            {
                abort(422 , 'Category not available!');
            }
            if($discountedPrice!==null&&$goodsCategory->is_default)
            {
                $validationField['discounted_price'] = $params['discounted_price'] = round(floatval($discountedPrice) , 2);
            }
            $categoryGoods = CategoryGoods::where('goods_id' , $goods->id)->first();
        }
        if($packaging_cost!==null)
        {
            $packaging_cost = floatval($packaging_cost);
            $validationField['packaging_cost'] = $params['packaging_cost'] = $packaging_cost;
        }
        try {
            Validator::make($validationField, $rules)->validate();
        } catch (ValidationException $exception) {
            throw new ValidationException($exception->validator);
        }
        $now = date("Y-m-d H:i:s");
        if(!empty($params))
        {
            if(isset($params['image']))
            {
                $image = $params['image'];
                $image = array_map(function($v){
                    unset($v['path']);
                    return $v;
                } , $image);
                $params['image'] = \json_encode($image , JSON_UNESCAPED_UNICODE);
            }
            $params['updated_at'] = $now;
            try {
                DB::beginTransaction();
                $goodsResult = DB::table('goods')->where('id' , $id)->update($params);
                if($goodsResult<=0)
                {
                    abort(500 , 'goods update failed!');
                }
                if(!empty($goodsCategory))
                {
                    if(!empty($categoryGoods))
                    {
                        if($categoryGoods->category_id!=$categoryId)
                        {
                            $data = array(
                                'category_id'=>$categoryId,
                                'sort'=>$sort,
                            );
                            isset($params['status'])&&$data['status'] = $params['status'];
                            $categoryGoodsResult = DB::table('categories_goods')->where('id' , $categoryGoods->id)->update($data);
                            if($categoryGoodsResult<=0)
                            {
                                abort(500 , 'category goods update failed!');
                            }
                            Redis::del("helloo:business:goods:category:service:account:".$user->user_id);
                            GoodsCategoryUpdate::dispatch(array($categoryGoods->category_id , $categoryId) , $user->user_id)->onQueue('helloo_{goods_category_update}');
                        }
                    }else{
                        $data = array(
                            'id'=>app('snowflake')->id(),
                            'category_id'=>$categoryId,
                            'goods_id'=>$goods->id,
                            'sort'=>$sort,
                            'user_id'=>$user->user_id,
                            'created_at'=>$now,
                        );
                        isset($params['status'])&&$data['status'] = $params['status'];
                        $categoryGoodsResult = DB::table('categories_goods')->insert($data);
                        if(!$categoryGoodsResult)
                        {
                            abort(500 , 'category goods insert failed!');
                        }
                        Redis::del("helloo:business:goods:category:service:account:".$user->user_id);
                        GoodsCategoryUpdate::dispatch(array($categoryId) , $user->user_id)->onQueue('helloo_{goods_category_update}');
                    }
                }
                DB::commit();
                $goods->makeVisible(array('extension_id'));
                $params['id'] = $goods->extension_id;
                $this->dispatch((new Bitrix24Product($params , __FUNCTION__))->onQueue('helloo_{bitrix_product}'));
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('goods_update_fail' , array(
                    'message'=>$e->getMessage(),
                    'user_id'=>$user->user_id,
                    'data'=>$request->all(),
                ));
                abort(500 , 'Sorry, this goods information update failed!');
            }
            if(isset($params['status'])&&$params['status']==1)
            {
                $price = $goods->currency=="BIRR"?$params['price']*0.023:$params['price'];
                Redis::zadd("helloo:discovery:price:products" , array(
                    $id=>$price
                ));
            }else{
                Redis::zrem("helloo:discovery:price:products" , $id);
            }
        }
        return $this->response->accepted();
    }

    /**
     * @note 商品点赞
     * @datetime 2021-07-12 17:55
     * @param Request $request
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function storeLike(Request $request , $id)
    {
        $user = auth()->user();
        app(GoodsRepository::class)->storeLike($user , $id);
        return $this->response->accepted();
    }

    /**
     * @deprecated
     * @note 商品取消点赞
     * @datetime 2021-07-12 17:55
     * @param Request $request
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroyLike(Request $request , $id)
    {
        $user = auth()->user();
        app(GoodsRepository::class)->destroyLike($user , $id);
        return $this->response->noContent();
    }

    /**
     * @note 商品的点赞列表
     * @datetime 2021-07-12 17:55
     * @param $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function like($id)
    {
        $likes = app(GoodsRepository::class)->like($id);
        $userIds = $likes->pluck('user_id')->unique()->toArray();
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $likes->each(function($like) use ($users){
            $like->user = new UserCollection($users->where('user_id' , $like->user_id)->first());
            $like->format_created_at = dateTrans($like->created_at);
        });
        return AnonymousCollection::collection($likes);
    }

    public function special(Request $request)
    {
        $appends = array();
        $pageName = 'page';
        $page = intval($request->input($pageName, 1));
        $page = $page<0?1:$page;
        $perPage = intval($request->input('per_page' , 10));
        $perPage = $perPage<10||$perPage>50?20:$perPage;
        $appends['per_page'] = $perPage;
        $specialGoods = DB::table('special_goods')->where('status' , 1)->select(['goods_id' , 'special_price'])->orderByDesc('updated_at')->paginate($perPage , ['*'] , $pageName , $page);
        $goodIds = $specialGoods->pluck('goods_id')->unique()->toArray();
        $goods = Goods::whereIn('id', $goodIds)->get();
        $shopIds = $goods->pluck('user_id')->unique()->toArray();
        $shops = app(UserRepository::class)->findByUserIds($shopIds);
        $goods->each(function($g) use ($specialGoods , $shops){
            $shop = $shops->where('user_id' , $g->user_id)->first();
            $specialG = $specialGoods->where('goods_id' , $g->id)->first();
            $g->specialPrice = $specialG->special_price;
            $g->user = new UserCollection($shop->only('user_id' , 'user_name' , 'user_nick_name'));
        });
        $goods = $this->paginator($goods , $specialGoods->total(), $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ])->appends($appends);
        return AnonymousCollection::collection($goods);
    }
}
