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
    /**
     * @note Dashboard 订单
     * @datetime 2021-07-12 18:00
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function order(Request $request)
    {
        $status = $request->input('status' , 'completed');
        $userId = auth()->id();
        if($status=='completed')
        {
            $orders = Order::where('shop_id' , $userId)->where('status' , 1)->orderByDesc('created_at')->paginate(10);
        }else if($status=='processing')
        {
            $orders = Order::where('shop_id' , $userId)->where('status' , 0)->orderByDesc('created_at')->paginate(10);
        }else if($status=='canceled')
        {
            $orders = Order::where('shop_id' , $userId)->where('status' , 2)->orderByDesc('created_at')->paginate(10);
        }else{
            $orders = collect();
        }
        $userIds = $orders->pluck('user_id')->unique()->toArray();
        if(!empty($userIds))
        {
            $users = app(UserRepository::class)->findByUserIds($userIds);
            $orders->each(function($order) use ($users){
                $order->user = new UserCollection($users->where('user_id' , $order->user_id)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address'));
                $order->delivery_coast = 30;
            });
        }
        return OrderCollection::collection($orders);
    }

    /**
     * @note Dashboard 订单统计
     * @datetime 2021-07-12 18:00
     * @param Request $request
     * @return mixed
     */
    public function statistics(Request $request)
    {
        $country = $request->input('country' , 'et');
        $userId = auth()->id();
        $orderPrice = DB::table('orders')->where('shop_id' , $userId)->where('status' , 1)->sum('order_price');
        if($country=='et')
        {
            $lastWeek = DB::table('orders')->where('shop_id' , $userId)->where('status' , 1)->whereBetween('created_at' , array(
                Carbon::now('Africa/Addis_Ababa')->previousWeekday()->startOfWeek()->subHours(3)->toDateTimeString(),Carbon::now('Africa/Addis_Ababa')->previousWeekday()->endOfWeek()->subHours(3)->toDateTimeString()
            ))->sum('order_price');
            $nowWeek = DB::table('orders')->where('shop_id' , $userId)->where('status' , 1)->whereBetween('created_at' , array(
                Carbon::now('Africa/Addis_Ababa')->startOfWeek()->subHours(3)->toDateTimeString(),Carbon::now('Africa/Addis_Ababa')->endOfWeek()->subHours(3)->toDateTimeString()
            ))->sum('order_price');
        }else{
            $lastWeek = DB::table('orders')->where('shop_id' , $userId)->where('status' , 1)->whereBetween('created_at' , array(
                Carbon::now()->previousWeekday()->startOfWeek()->toDateTimeString(),Carbon::now('Africa/Addis_Ababa')->previousWeekday()->endOfWeek()->toDateTimeString()
            ))->sum('order_price');
            $nowWeek = DB::table('orders')->where('shop_id' , $userId)->where('status' , 1)->whereBetween('created_at' , array(
                Carbon::now()->startOfWeek()->toDateTimeString(),Carbon::now('Africa/Addis_Ababa')->endOfWeek()->toDateTimeString()
            ))->sum('order_price');
        }
        return $this->response->array(
            array(
                'orderPrice'=>$orderPrice,
                'lastWeek'=>$lastWeek,
                'nowWeek'=>$nowWeek,
            )
        );
    }

    /**
     * @note 订单绘画
     * @datetime 2021-07-12 18:01
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function draw(Request $request)
    {
        $time = $request->input('time' , '');
        $goodsId = $request->input('goods_id' , '');
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
        }elseif ($time=='evening')
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
        if(!empty($goodsId))
        {
            $ordersGoods = DB::table('orders_goods')->where('goods_id' , $goodsId)->whereBetween(DB::raw("ATE_FORMAT(`created_at`, '%Y-%m-%d')") , array($startData , $endDate))->select('order_id')->get();
            $orderIds = $ordersGoods->pluck('order_id')->toArray();
        }
        if(empty($orderIds))
        {
            $sql = <<<DOC
SELECT count(*) as `total`,DATE_FORMAT(`created_at`, '%Y-%m-%d') as `date` FROM `t_orders` WHERE `shop_id`={$userId} AND DATE_FORMAT(`created_at`, '%Y-%m-%d') BETWEEN '{$startData}' AND '{$endDate}'
DOC;
        }else{
            $orderIds = trim(implode(',' , $orderIds) , ',');
            $sql = <<<DOC
SELECT count(*) as `total`,DATE_FORMAT(`created_at`, '%Y-%m-%d') as `date` FROM `t_orders` WHERE `shop_id`={$userId} AND `order_id` in ({$orderIds}) DATE_FORMAT(`created_at`, '%Y-%m-%d') BETWEEN '{$startData}' AND '{$endDate}'
DOC;
        }
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
