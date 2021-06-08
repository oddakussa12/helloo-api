<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryOrderController extends BaseController
{

    public function store(Request $request)
    {
        $userId = auth()->id();
        $owner = $request->input('user_id' , '');
        $goodsId = $request->input('goods_id' , '');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $createdAt = $updatedAt = date('Y-m-d H:i:s');
        $orderResult = DB::table('delivery_orders')->insert(array(
            'order_id'=>app('snowflake')->id(),
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
        }
        return $this->response->created();
    }

}
