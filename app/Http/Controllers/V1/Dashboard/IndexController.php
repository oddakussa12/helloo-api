<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Models\Business\Order;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\UserRepository;


class IndexController extends BaseController
{
    public function index(Request $request)
    {
        $status = $request->input('status' , 'completed');
        $userId = auth()->id();
        if($status=='completed')
        {
            $orders = Order::where('user_id' , $userId)->where('status' , 1)->orderByDesc('created_at')->paginate(10);
        }else if($status=='processing')
        {
            $orders = Order::where('user_id' , $userId)->where('status' , 0)->orderByDesc('created_at')->paginate(10);
        }else if($status=='canceled')
        {
            $orders = Order::where('user_id' , $userId)->where('status' , 2)->orderByDesc('created_at')->paginate(10);
        }else{
            $orders = collect();
        }
        $shopIds = $orders->pluck('shop_id')->unique()->toArray();
        if(!empty($shopIds))
        {
            $shops = app(UserRepository::class)->findByUserIds($shopIds);
            $orders->each(function($order) use ($shops){
                $order->shop = new UserCollection($shops->where('user_id' , $order->shop_id)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address'));
                $order->delivery_coast = 30;
            });
        }
        return OrderCollection::collection($orders);
    }

    public function statistics(Request $request)
    {
        $userId = auth()->id();
        DB::table('orders')->where('shop_id' , $userId)->where('status' , 1)->sum('order_price');
        DB::table('orders')->where('shop_id' , $userId)->where('status' , 1)->sum('brokerage');
    }

    public function order(Request $request)
    {
        $time = $request->input('time' , ' _ ');
        $time = explode(' - ' , $time);
        $startTime = array_shift($time);
        $endTime = array_pop($time);
        $order = DB::table('orders');
        if(!empty($startTime)&&!empty($endTime))
        {
            if(date('H:i' , strtotime($startTime))!=$startTime||date('H:i' , strtotime($endTime))!=$endTime)
            {
                return $this->response->noContent();
            }

        }
        $data = $request->input('date' , ' _ ');
        $data = explode(' - ' , $data);
        $startData = array_shift($data);
        $endDate = array_pop($data);
        if(!empty($startData)&&!empty($endDate))
        {
            if(date('Y-m-d' , strtotime($startData))!=$startData||date('Y-m-d' , strtotime($endDate))!=$endDate)
            {
                return $this->response->noContent();
            }
        }
    }

}
