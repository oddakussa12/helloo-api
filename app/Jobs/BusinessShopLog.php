<?php

namespace App\Jobs;

use Ramsey\Uuid\Uuid;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BusinessShopLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $user;
    private $shop;
    private $owner;
    private $now;

    public function __construct($user , $shop , $owner)
    {
        $this->user = $user;
        $this->shop = $shop;
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
        $shop = DB::table('shops_views')->where('shop_id' , $this->shop)->first();
        if(empty($shop))
        {
            DB::table('shops_views')->insert(array(
                'id'=>Uuid::uuid1()->toString(),
                'shop_id'=>$this->shop,
                'owner'=>$this->owner,
                'num'=>1,
                'created_at'=>$this->now,
                'updated_at'=>$this->now,
            ));
        }else{
            DB::table('shops_views')->where('id' , $shop->id)->increment('num');
        }
        DB::table('shops_views_logs')->insert(array(
            'id'=>Uuid::uuid1()->toString(),
            'user_id'=>$this->user,
            'shop_id'=>$this->shop,
            'owner'=>$this->owner,
            'created_at'=>$this->now,
        ));
    }

}
