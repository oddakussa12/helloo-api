<?php

namespace App\Http\Controllers\V1\Shop;

use App\Models\Shop\Shop;
use App\Models\UserFriend;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ShopController extends BaseController
{

    public function index(Request $request)
    {
        $params = $request->only('keyword');
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
        $userId = auth()->user()->user_id;
        $shop = Shop::where('id', $id)->first();
        if (!empty($shop)) {
            $shop->state = false;
            if ($userId!=$shop->user_id) {
                $friend = UserFriend::where('user_id', $userId)->where('friend_id', $shop->user_id)->first();
                $shop->state = $friend ? 'friend' : '';
            } else {
                $shop->state = false;
            }
        }
        return $this->response->array(['data'=>$shop]);
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
        $params = $request->only('name', 'nick_name', 'avatar', 'cover', 'address', 'phone', 'description');
        $rules = [
            'name'        => ['bail', 'filled', 'string', 'regex:/^[a-zA-Z0-9_-]{6,32}$/'],
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

        $user = auth()->user();
        if (empty($user->user_shop)) {
            return $this->response->errorForbidden();
        }
        // 判断name是否重名
        if(!empty($params['name'])) {
            $result = Shop::where('name', $params['name'])->where('user_id', '!=', $user->user_id)->first();
            if ($result) {
                return $this->response->errorBadRequest('名称已存在，请更换一个');
            }
        }
        Shop::where(['user_id'=>$user->user_id, 'id'=>$id])->update($params);
        return $this->response->accepted();
    }
}
