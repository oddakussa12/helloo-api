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

    private $type;

    public function __construct($returnData , $type='default')
    {
        $this->returnData = $returnData;
        $this->type = $type;
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
        if($this->type=='special')
        {
            $detail = $returnData['detail'];
            $data = array(
                'id'=>Uuid::uuid1()->toString(),
                'user_id'=>$detail['user_id'],
                'order_id'=>$detail['order_id'],
                'shop_id'=>$detail['shop_id'],
                'goods_id'=>$detail['id'],
                'goods_name'=>$detail['name'],
                'goods_price'=>$detail['price'],
                'discounted_price'=> $detail['discounted_price'] ?? 0,
                'goods_number'=>$detail['goodsNumber'],
                'goods_image'=>\json_encode($detail['image'] , JSON_UNESCAPED_UNICODE),
                'goods_currency'=>$detail['currency'],
                'created_at'=>$detail['created_at'],
            );
            DB::table('orders_goods')->insert($data);
        }else{
            $data = array();
            foreach ($returnData as $r)
            {
                foreach ($r['detail'] as $g)
                {
                    array_push($data , array(
                        'id'=>Uuid::uuid1()->toString(),
                        'user_id'=>$r['user_id'],
                        'order_id'=>$r['order_id'],
                        'shop_id'=>$r['shop_id'],
                        'goods_id'=>$g['id'],
                        'goods_name'=>$g['name'],
                        'goods_price'=>$g['price'],
                        'discounted_price'=> $g['discounted_price'] ?? 0,
                        'goods_number'=>$g['goodsNumber'],
                        'goods_image'=>\json_encode($g['image'] , JSON_UNESCAPED_UNICODE),
                        'goods_currency'=>$g['currency'],
                        'created_at'=>$r['created_at'],
                    ));
                }
            }
            !empty($data)&&DB::table('orders_goods')->insert($data);
        }
    }

}
