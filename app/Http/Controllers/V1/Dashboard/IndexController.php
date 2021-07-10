<?php

namespace App\Http\Controllers\V1\Dashboard;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Business\Order;
use App\Resources\UserCollection;
use App\Resources\OrderCollection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;


class IndexController extends BaseController
{
    public function order(Request $request)
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

    public function draw(Request $request)
    {
        $time = $request->input('time' , '');
        if($time=='morning')
        {
            $hours = array(
                '09',
                '10',
                '11',
                '12',
                '13',
            );
        }elseif ($time=='afternoon')
        {
            $hours = array(
                '14',
                '15',
                '16',
                '17',
            );
        }elseif ($time=='afternoon')
        {
            $hours = array(
                '18',
                '19',
                '20',
                '21',
                '22',
            );
        }else{
            $hours = array();
        }
        $date = $request->input('date' , date('Y-m-d' , strtotime("-8 day")) . '_'.date('Y-m-d' , strtotime("-1 day")));
        $date = explode('_' , $date);
        $startData = array_shift($date);
        $endDate = array_pop($date);
        if(!empty($startData)&&!empty($endDate))
        {
            if(date('Y-m-d' , strtotime($startData))!=$startData||date('Y-m-d' , strtotime($endDate))!=$endDate)
            {
                return $this->response->noContent();
            }
        }else{
            return $this->response->noContent();
        }
        if(Carbon::createFromFormat("Y-m-d" , $startData)->diffInMonths($endDate)>=1)
        {
            abort(422 , 'Date interval is too long!');
        }
        $userId = auth()->id();
        $sql = <<<DOC
SELECT count(*) as `total`,DATE_FORMAT(`created_at`, '%Y-%m-%d') as `date` FROM `t_orders` WHERE `user_id`={$userId} AND DATE_FORMAT(`created_at`, '%Y-%m-%d') BETWEEN '{$startData}' AND '{$endDate}'
DOC;
        if(!empty($hours))
        {
            $sql .= ' AND DATE_FORMAT(`created_at`, "%H") IN ('.trim(implode(',' , $hours) , ',').')';
        }
        $sql .= " GROUP BY `date`";
        $data = collect(DB::select($sql))->pluck('total' , 'date')->toArray();
        $statistics = array();
        while ($startData<=$endDate)
        {
            if(empty($data[$startData]))
            {
                $statistics[$startData] = 0;
            }else{
                $statistics[$startData] = $data[$startData];
            }
            $startData = date("Y-m-d",strtotime("+1 day",strtotime($startData)));
        }
        return $this->response->array(array('data'=>$statistics));
    }

}
