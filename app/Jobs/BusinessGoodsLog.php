<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Ramsey\Uuid\Uuid;

class BusinessGoodsLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $user;
    private $shop;
    private $goods;
    private $owner;
    private $now;

    public function __construct($user , $shop , $goods , $owner)
    {
        $this->user = $user;
        $this->shop = $shop;
        $this->goods = $goods;
        $this->owner = $owner;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $goods = DB::table('goods_views')->where('goods_id' , $this->goods)->first();
        if(empty($goods))
        {
            DB::table('goods_views')->insert(array(
                'id'=>Uuid::uuid1()->toString(),
                'shop_id'=>$this->shop,
                'goods_id'=>$this->goods,
                'owner'=>$this->owner,
                'num'=>1,
                'created_at'=>$this->now,
                'updated_at'=>$this->now,
            ));
        }else{
            DB::table('goods_views')->where('id' , $goods->id)->increment('num');
        }
        DB::table('goods_views_logs')->insert(array(
            'id'=>Uuid::uuid1()->toString(),
            'user_id'=>$this->user,
            'shop_id'=>$this->shop,
            'goods_id'=>$this->goods,
            'owner'=>$this->owner,
            'created_at'=>$this->now,
        ));
    }

}
