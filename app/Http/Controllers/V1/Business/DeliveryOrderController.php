<?php

namespace App\Http\Controllers\V1\Business;

use App\Jobs\OrderSms;
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

    /**
     * @note delivery order 下单
     * @datetime 2021-07-12 17:49
     * @param StoreDeliveryOrderRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(StoreDeliveryOrderRequest $request)
    {
        $userId = auth()->id();
        $owner = strval($request->input('user_id' , ''));
        $goodsId = $request->input('goods_id' , '');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $createdAt = $updatedAt = date('Y-m-d H:i:s');
        $o = app(UserRepository::class)->findByUserId($owner);
        if(blank($o))
        {
            abort(404 , 'Shop does not exist!');
        }
        $orderId = app('snowflake')->id();
        $orderInfo = array(
            'order_id'=>$orderId,
            'user_id'=>$userId,
            'owner'=>$owner,
            'goods_id'=>$goodsId,
            'user_name'=>$userName,
            'user_contact'=>$userContact,
            'user_address'=>$userAddress,
            'created_at'=>$createdAt,
            'updated_at'=>$updatedAt,
        );
        $orderResult = DB::table('delivery_orders')->insert($orderInfo);
        if(!$orderResult)
        {
            Log::info('order_create_fail' , array(
                'user_id'=>$userId,
                'data'=>$request->all()
            ));
        }else{
            $orderItem = [];
            $totalOrderCost = 0;
            $user = app(UserRepository::class)->findByUserId($owner);
            if(!empty($goodsId))
            {
                $goods = app(GoodsRepository::class)->find($goodsId);
                $orderItem = \json_encode([["name" => $goods->name, "unitPrice" => $goods->price, "quantity" => 1, "detail" => ""]]);
                $totalOrderCost = $totalOrderCost+$goods->price;
            }
            if(!blank($user))
            {
                Shipday::dispatch($orderId , $userName , $userAddress , $userContact , strval($user->get('user_nick_name' , '')) , strval($user->get('user_address' , '')) , strval($user->get('user_contact' , '')) , $orderItem , $totalOrderCost , 0)->onQueue('helloo_{delivery_shipday}');
                OrderSms::dispatch($orderInfo)->onQueue('helloo_{delivery_order_sms}');
            }
        }
        return $this->response->created();
    }

}
