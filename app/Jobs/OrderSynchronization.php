<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Ramsey\Uuid\Uuid;

class OrderSynchronization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $returnData;

    public function __construct($returnData)
    {
        $this->returnData = $returnData;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $returnData = $this->returnData;
        $data = array();
        foreach ($returnData as $r)
        {
            foreach ($r['detail'] as $g)
            {
                array_push($data , array(
                    'id'=>Uuid::uuid1()->toString(),
                    'user_id'=>$g['user_id'],
                    'order_id'=>$g['order_id'],
                    'shop_id'=>$g['shop_id'],
                    'goods_id'=>$g['id'],
                    'goods_name'=>$g['name'],
                    'goods_price'=>$g['price'],
                    'goods_image'=>$g['image'],
                    'goods_currency'=>$g['currency'],
                    'created_at'=>$r['created_at'],
                ));
            }
        }
        !empty($data)&&DB::table('orders_goods')->insert($data);
    }

}
