<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\UserFriend;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use App\Models\Business\Shop;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Resources\AnonymousCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\V1\BaseController;
use Illuminate\Validation\ValidationException;
use App\Repositories\Contracts\UserRepository;

class ShopController extends BaseController
{

    public function index(Request $request)
    {
        $params = strval($request->only('keyword'));
        return Shop::select('id', 'avatar', 'cover', 'nick_name', 'address')
            ->where('name', 'like', "%{$params['keyword']}%")
            ->where('nick_name', 'like', "%{$params['keyword']}%")
            ->paginate(10);
    }

    /**
     * @return mixed
     * 店铺推荐
     */
    public function recommend()
    {
        $result = Shop::select('id', 'nick_name', 'avatar')->where('recommend', 1)->orderByDesc('recommended_at')->limit(10)->get();
        if ($result->isEmpty()) {
            $result = Shop::select('id', 'nick_name', 'avatar')->orderBy(DB::raw('rand()'))->limit(10)->get();
        }
        return $this->response->array(['data'=>$result]);
    }

    /**
     * @param $id
     * @return mixed
     * 店铺详情
     */
    public function show($id)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $shop = Shop::findOrFail($id);
        $shop->user = new UserCollection(app(UserRepository::class)->findByUserId($shop->user_id));
        if ($userId!=$shop->user_id) {
            $friend = UserFriend::where('user_id', $userId)->where('friend_id', $shop->user_id)->first();
            $shop->user->state = $friend ? 'friend' : false;
        } else {
            $shop->user->state = 'self';
        }
        return new AnonymousCollection($shop);
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response|void
     * @throws ValidationException
     * 修改店铺信息
     */
    public function update(Request $request , $id)
    {
        $user = auth()->user();
        $params = $request->only('name', 'nick_name', 'avatar', 'cover', 'address', 'phone', 'description');
        $rules = [
            'name' => [
                'bail',
                'filled',
                'string',
                'regex:/^[a-zA-Z0-9_-]{6,32}$/' ,
                function ($attribute, $value, $fail) use ($user){
                    $shop = Shop::where('name', $value)->where('user_id', '!=', $user->user_id)->first();
                    if(!empty($shop))
                    {
                        $fail('Store ID already exists!');
                    }
                }
             ],
            'nick_name'   => ['bail', 'filled', 'string', 'regex:/^[a-zA-Z0-9_-]{6,32}$/'],
            'avatar'      => ['bail', 'filled', 'string', 'min:30', 'max:300'],
            'cover'       => ['bail', 'filled', 'string', 'min:30', 'max:300'],
            'address'     => ['bail', 'filled', 'string', 'min:10', 'max:100'],
            'phone'       => ['bail', 'filled', 'string', 'min:5'],
            'description' => ['bail', 'filled', 'string', 'max:300'],
        ];

        try {
            Validator::make($params, $rules)->validate();
        } catch (ValidationException $exception) {
            throw new ValidationException($exception->validator);
        }
        if ($user->user_shop!=$id) {
            abort(403 , 'Shop does not exist!');
        }
        !empty($params)&&Shop::where('id' , $id)->update($params);
        return $this->response->accepted();
    }
}
