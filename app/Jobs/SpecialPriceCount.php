<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class SpecialPriceCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->orders as $order)
        {
            $time = $order['created_at'];
            $date = date('Y-m-d' , strtotime($time));
            foreach ($order['detail'] as $d)
            {
                $goods = DB::table('special_counts')->where('goods_id' , $d['id'])->where('date' , $date)->first();
                if(empty($goods))
                {
                    DB::table('special_counts')->insert(array(
                        'goods_id'=>$d['id'],
                        'goods_name'=>$d['name'],
                        'shop_id'=>$order['shop_id'],
                        'total_price'=>$d['price'],
                        'total_purchase_price'=>$d['purchase_price'],
                        'special_price'=>$d['specialPrice'],
                        'num'=>1,
                        'date'=>$date,
                        'created_at'=>$time,
                    ));
                }else{
                    DB::table('special_counts')->where('goods_id' , $d['id'])->where('date' , $date)->update(array(
                        'total_price'=>DB::raw('total_price+'.$d['price']),
                        'total_purchase_price'=>DB::raw('total_purchase_price+'.$d['purchase_price']),
                        'special_price'=>DB::raw('special_price+'.$d['specialPrice']),
                        'num'=>DB::raw('num+1'),
                    ));
                }
            }
        }
    }

}
