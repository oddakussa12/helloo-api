<?php

namespace App\Http\Controllers\V1\Business;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use App\Jobs\BusinessGoodsLog;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
use Illuminate\Validation\Rule;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $keyword = escape_like(strval($request->input('keyword' , '')));
        $auth = intval(auth()->id());
        $userId = strval($request->input('user_id' , ''));
        $type = strval($request->input('type' , ''));
        $version = $request->input('version' , 'v1');
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
                $goods = Goods::where('user_id', $userId);
                $type != 'management' && $goods = $goods->where('status' , 1);
                $goods = $goods->orderByDesc('created_at')->paginate(10)->appends($appends);
            }else{

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
        return AnonymousCollection::collection($goods);
    }

    /**
     * @return mixed
     * @note goods recommendation
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
        return new AnonymousCollection($goods);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $name = strval($request->input('name' , ''));
        $image = $request->input('image' , '');
        $price = $request->input('price');
        $status = $request->input('status');
        $description = strval($request->input('description' , ''));
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
                'filled',
                Rule::in(array(1 , 0 , '1' , '0'))
            ],
        ];
        $data = $validationField = array(
            'user_id'=>$userId,
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
        $image = array_map(function($v){
            unset($v['path']);
            return $v;
        } , $image);
        $now = date("Y-m-d H:i:s");
        $data['id'] = Uuid::uuid1()->toString();
        $data['user_id'] = $userId;
        $data['image'] = \json_encode($image , JSON_UNESCAPED_UNICODE);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $phone = DB::table('users_phones')->where('user_id' , $user->user_id)->first();
        if(!empty($phone)&&$phone->user_phone_country=='251')
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
        $validationField['user_id'] = $goods->user_id;
        $rules = [
            'user_id' => [
                'bail',
                'filled',
                function ($attribute, $value, $fail) use ($user , $goods){
                    if($goods->user_id!=$user->user_id)
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
                $image = $params['image'];
                $image = array_map(function($v){
                    unset($v['path']);
                    return $v;
                } , $image);
                $params['image'] = \json_encode($image , JSON_UNESCAPED_UNICODE);
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

    public function destroyLike(Request $request , $id)
    {
        $user = auth()->user();
        app(GoodsRepository::class)->destroyLike($user , $id);
        return $this->response->noContent();
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
