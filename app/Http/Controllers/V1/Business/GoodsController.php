<?php

namespace App\Http\Controllers\V1\Business;

use App\Jobs\BusinessGoodsLog;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use App\Models\Business\Shop;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
use Illuminate\Validation\Rule;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Resources\AnonymousCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Validation\ValidationException;
use App\Repositories\Contracts\GoodsRepository;

class GoodsController extends BaseController
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $keyword = escape_like(strval($request->input('keyword' , '')));
        $shopId = strval($request->input('shop_id' , ''));
        $appends['keyword'] = $keyword;
        $appends['shop_id'] = $shopId;
        if(!empty($keyword))
        {
            $goods = Goods::where('shop_id', $shopId)->where('status' , 1)->where('name', 'like', "%{$keyword}%")->limit(10)->get();
            BusinessSearchLog::dispatch($userId , $keyword , $shopId)->onQueue('helloo_{business_search_log}');
        }elseif (!empty($shopId))
        {
            $goods = Goods::where('shop_id', $shopId)
                ->orderByDesc('created_at')
                ->paginate(10);
            $goods = $goods->appends($appends);
        }else{
            $goods = collect();
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
     * @return mixed
     * @note goods recommendation
     */
    public function recommendation()
    {
        $userId = auth()->id();
        $goods = Goods::where('status' , 1)->select('id', 'shop_id', 'name' , 'image' , 'like' , 'price' , 'currency')->where('recommend', 1)->orderByDesc('recommended_at')->limit(10)->get();
        if ($goods->isEmpty()) {
            $goods = Goods::where('status' , 1)->select('id', 'shop_id', 'name' , 'image' , 'like' , 'price' , 'currency')->orderBy(DB::raw('rand()'))->limit(10)->get();
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

    public function show($id)
    {
        $userId = auth()->id();
        $action = strval(request()->input('action' , ''));
        $referrer = strval(request()->input('referrer' , ''));
        $goods = Goods::where('id' , $id)->firstOrFail();
        $like = DB::table('likes_goods')->where('id' , strval(auth()->id())."-".$id)->first();
        $goods = $goods->makeVisible('status');
        $goods->likeState = !empty($like);
        if($action=='view'&&$goods->user_id!=$userId)
        {
            BusinessGoodsLog::dispatch($userId , $goods->shop_id , $id , $goods->user_id , $referrer)->onQueue('helloo_{business_goods_logs}');
        }
        return new AnonymousCollection($goods);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $shopId = strval($request->input('shop_id' , ''));
        $name = strval($request->input('name' , ''));
        $image = $request->input('image' , '');
        $price = $request->input('price');
        $status = $request->input('status');
        $description = strval($request->input('description' , ''));
        $rules = [
            'shop_id' => [
                'bail',
                'filled',
                function ($attribute, $value, $fail) use ($user){
                    if(empty($value)||$user->user_shop!=$value)
                    {
                        $fail('Shop does not exist!');
                    }
                }
            ],
            'name' => [
                'bail',
                'required',
                'string',
                'between:1,24'
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
                'filled',
                Rule::in(array(1 , 0 , '1' , '0'))
            ],
        ];
        $data = $validationField = array(
            'shop_id'=>$shopId,
            'name'=>$name,
            'image'=>$image,
            'price'=>$price,
            'description'=>$description,
            'status'=>$status
        );
        try {
            Validator::make($validationField, $rules)->validate();
        } catch (ValidationException $exception) {
            throw new ValidationException($exception->validator);
        }
        $shop = Shop::where('id' , $shopId)->firstOrFail();
        $now = date("Y-m-d H:i:s");
        $data['id'] = Uuid::uuid1()->toString();
        $data['user_id'] = $userId;
        $data['image'] = \json_encode($image , JSON_UNESCAPED_UNICODE);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        if($shop->country=='et')
        {
            $data['currency'] = 'BIRR';
        }else
        {
            $data['currency'] = 'USD';
        }
        try{
            DB::beginTransaction();
            $goodsResult = DB::table('goods')->insert($data);
            if(!$goodsResult)
            {
                abort(405 , 'goods insert failed!');
            }
            $shopResult = DB::table('shops')->where('id' , $shopId)->increment('goods');
            if($shopResult<=0)
            {
                abort(405 , 'shop update failed!');
            }
            DB::commit();
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

    public function update(Request $request , $id)
    {
        $user = auth()->user();
        $goods = Goods::where('id' , $id)->firstOrFail();
        $params = $validationField = $request->only(array('name' , 'image' , 'price' , 'status' , 'description'));
        $validationField['shop_id'] = $goods->shop_id;
        $rules = [
            'shop_id' => [
                'bail',
                'filled',
                function ($attribute, $value, $fail) use ($user , $goods){
                    if($user->user_shop!=$value||$goods->user_id!=$user->user_id)
                    {
                        $fail('Shop does not exist!');
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
        ];
        try {
            Validator::make($validationField, $rules)->validate();
        } catch (ValidationException $exception) {
            throw new ValidationException($exception->validator);
        }
        if(!empty($params))
        {
            if(isset($params['image']))
            {
                $params['image'] = \json_encode($params['image'] , JSON_UNESCAPED_UNICODE);
            }
            DB::table('goods')->where('id' , $id)->update($params);
        }
        return $this->response->accepted();
    }

    public function storeLike(Request $request , $id)
    {
        $user = auth()->user();
        app(GoodsRepository::class)->storeLike($user , $id);
        return $this->response->accepted();
    }

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
}
