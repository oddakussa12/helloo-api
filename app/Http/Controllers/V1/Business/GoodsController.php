<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\Business\Shop;
use App\Models\Business\Goods;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\GoodsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GoodsController extends BaseController
{
    public function index(Request $request)
    {
        $keyword = strval($request->input('keyword' , ''));
        $shopId = strval($request->input('shop_id' , ''));
        if(!empty($keyword))
        {
            $goods = Goods::where('name', 'like', "%{$keyword}%")
                ->paginate(10);
        }elseif (!empty($shopId))
        {
            $goods = Goods::where('shop_id', $shopId)
                ->orderByDesc('created_at')
                ->paginate(10);
        }else{
            $goods = array();
        }
        return AnonymousCollection::collection($goods);
    }

    public function show($id)
    {
        $goods = Goods::where('id' , $id)->firstOrFail();
        return new AnonymousCollection($goods);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
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
                    if(empty($value)||$user->shop!=$value)
                    {
                        $fail('Shop does not exist!');
                    }
                }
            ],
            'name' => [
                'bail',
                'required',
                'string',
                'between:6,24'
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
        $now = date("Y-m-d H:i:s");
        $data['image'] = \json_encode($image , JSON_UNESCAPED_UNICODE);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        DB::table('goods')->insert($data);
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
        !empty($params)&&DB::table('goods')->where('id' , $id)->update($params);
        return $this->response->accepted();
    }

    public function like(Request $request , $id)
    {
        $user = auth()->user();
        app(GoodsRepository::class)->like($user , $id);
        return $this->response->accepted();
    }
}
