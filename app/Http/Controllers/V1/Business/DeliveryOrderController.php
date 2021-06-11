<?php

namespace App\Http\Controllers\V1\Business;

use App\Jobs\Shipday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\GoodsRepository;
use App\Http\Requests\StoreDeliveryOrderRequest;

class DeliveryOrderController extends BaseController
{

    public function store(StoreDeliveryOrderRequest $request)
    {
        $userId = auth()->id();
        $owner = strval($request->input('user_id' , ''));
        $goodsId = $request->input('goods_id' , '');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $createdAt = $updatedAt = date('Y-m-d H:i:s');
        $orderId = app('snowflake')->id();
        $orderResult = DB::table('delivery_orders')->insert(array(
            'order_id'=>$orderId,
            'user_id'=>$userId,
            'owner'=>$owner,
            'goods_id'=>$goodsId,
            'user_name'=>$userName,
            'user_contact'=>$userContact,
            'user_address'=>$userAddress,
            'created_at'=>$createdAt,
            'updated_at'=>$updatedAt,
        ));
        if(!$orderResult)
        {
            Log::info('order_create_fail' , array(
                'user_id'=>$userId,
                'data'=>$request->all()
            ));
        }else{
            $orderItem = [];
            $user = app(UserRepository::class)->findByUserId($owner);
            if(!empty($goodsId))
            {
                $goods = app(GoodsRepository::class)->find($goodsId);
                Log::info('$goods' , collect($goods)->toArray());
                $orderItem = [$goods->name];
            }
            if(!blank($user))
            {
                Shipday::dispatch($orderId , $userName , $userAddress , $userContact , strval($user->get('user_nick_name' , '')) , strval($user->get('user_address' , '')) , strval($user->get('user_contact' , '')) , $orderItem , 0 , 0)->onQueue('helloo_{delivery_shipday}');
            }
        }
        return $this->response->created();
    }

}
