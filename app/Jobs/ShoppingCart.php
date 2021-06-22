<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ShoppingCart implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $goods;

    private $user;

    private $number;

    private $now;


    public function __construct($goods , $user , $number=0)
    {
        $this->goods = $goods;
        $this->user = $user;
        $this->number = $number;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $goods = $this->goods;
        $user = $this->user;
        $userId = $user->user_id;
        $goodsId = $goods->id;
        if($this->number==0)
        {
            DB::table('shopping_carts')->where('user_id' , $userId)->where('goods_id' , $goodsId)->delete();
        }else{
            $shoppingCart = DB::table('shopping_carts')->where('user_id' , $userId)->where('goods_id' , $goodsId)->first();
            if(!empty($shoppingCart))
            {
                $id = $shoppingCart->id;
                DB::table('shopping_carts')->where('id' , $id)->update(
                    array(
                        'number'=>$this->number,
                        'updated_at'=>$this->now,
                    )
                );
            }else{
                $id = app('snowflake')->id();
                DB::table('shopping_carts')->insert(array(
                    'id'=>$id,
                    'user_id'=>$userId,
                    'goods_id'=>$goodsId,
                    'shop_id'=>$goods->user_id,
                    'number'=>$this->number,
                    'created_at'=>$this->now,
                    'updated_at'=>$this->now,
                ));
            }
        }
    }

}
